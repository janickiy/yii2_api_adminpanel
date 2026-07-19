<?php

declare(strict_types=1);

namespace common\repositories;

use Closure;
use common\entities\Note;
use common\services\EventLoggerInterface;
use InvalidArgumentException;
use Throwable;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;

final readonly class NoteRepository implements NoteRepositoryInterface
{
    public function __construct(
        private CacheInterface $cache,
        private EventLoggerInterface $logger,
        private int $cacheTtl = 120,
    ) {
        if ($this->cacheTtl < 1) {
            throw new InvalidArgumentException('The notes cache TTL must be positive.');
        }
    }

    public function findOwnedById(int $id, int $userId): ?Note
    {
        $key = [NoteCacheTags::NAMESPACE, 'item', 'user', $userId, 'note', $id];

        try {
            $attributes = $this->remember(
                $key,
                $userId,
                fn (): ?array => $this->loadOwnedById($id, $userId),
                [NoteCacheTags::user($userId)],
                static fn (mixed $value): bool => $value === null
                    || self::isOwnedNoteRow($value, $id, $userId),
            );

            return is_array($attributes) ? self::hydrate($attributes) : null;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to find the note.', $exception);
        }
    }

    public function findAllOwned(
        int $userId,
        ?int $categoryId,
        int $limit,
        int $offset,
    ): array {
        $this->assertPagination($limit, $offset);
        $categoryKey = $categoryId ?? 'all';
        $key = [
            NoteCacheTags::NAMESPACE,
            'list',
            'user',
            $userId,
            'category',
            $categoryKey,
            'limit',
            $limit,
            'offset',
            $offset,
        ];

        try {
            $rows = $this->remember(
                $key,
                $userId,
                fn (): array => $this->loadAllOwned($userId, $categoryId, $limit, $offset),
                [NoteCacheTags::user($userId)],
                static fn (mixed $value): bool => is_array($value)
                    && self::containsOnlyOwnedNoteRows($value, $userId, $categoryId),
            );

            if (!is_array($rows) || !self::containsOnlyOwnedNoteRows($rows, $userId, $categoryId)) {
                throw new PersistenceException('The notes cache returned an unexpected value.');
            }

            return array_map(self::hydrate(...), $rows);
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to list notes.', $exception);
        }
    }

    public function countOwned(int $userId, ?int $categoryId): int
    {
        $categoryKey = $categoryId ?? 'all';
        $key = [
            NoteCacheTags::NAMESPACE,
            'count',
            'user',
            $userId,
            'category',
            $categoryKey,
        ];

        try {
            $count = $this->remember(
                $key,
                $userId,
                fn (): int => $this->loadCountOwned($userId, $categoryId),
                [NoteCacheTags::user($userId)],
                static fn (mixed $value): bool => is_int($value) && $value >= 0,
            );

            if (!is_int($count)) {
                throw new PersistenceException('The notes count cache returned an unexpected value.');
            }

            return $count;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to count notes.', $exception);
        }
    }

    public function findById(int $id): ?Note
    {
        try {
            $note = Note::findOne(['id' => $id]);

            return $note instanceof Note ? $note : null;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to find the note.', $exception);
        }
    }

    public function query(): ActiveQuery
    {
        return Note::find()
            ->with(['user', 'category'])
            ->orderBy(['id' => SORT_DESC]);
    }

    public function count(): int
    {
        try {
            return (int) $this->query()->count();
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to count notes.', $exception);
        }
    }

    public function save(Note $note): Note
    {
        $oldUserId = $note->getIsNewRecord()
            ? null
            : self::positiveInt($note->getOldAttribute('user_id'));
        if ($oldUserId !== null && $oldUserId !== (int) $note->user_id) {
            throw new PersistenceException('Changing the owner of an existing note is not allowed.');
        }

        $transaction = null;

        try {
            $transaction = Note::getDb()->beginTransaction();
            if (!$note->save()) {
                throw PersistenceException::fromModel('Unable to save the note.', $note);
            }

            if (!$note->refresh()) {
                throw new PersistenceException('The note was saved but could not be reloaded.');
            }

            $transaction->commit();
        } catch (Throwable $exception) {
            if ($transaction !== null && $transaction->isActive) {
                $transaction->rollBack();
            }

            throw PersistenceException::wrap('Unable to save the note.', $exception);
        }

        $this->invalidateUserCache((int) $note->user_id);

        return $note;
    }

    public function delete(Note $note): void
    {
        $id = self::positiveInt($note->getPrimaryKey());
        $userId = self::positiveInt($note->user_id);

        if ($id === null || $userId === null) {
            throw new PersistenceException('Cannot delete a note without an id and owner.');
        }

        try {
            $record = Note::findOne(['id' => $id, 'user_id' => $userId]);

            if (!$record instanceof Note) {
                $this->invalidateUserCache($userId);

                return;
            }

            $deleted = $record->delete();
            if ($deleted === false) {
                throw new PersistenceException('The note could not be deleted.');
            }
            $this->invalidateUserCache($userId);
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to delete the note.', $exception);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadOwnedById(int $id, int $userId): ?array
    {
        $row = Note::find()
            ->where(['id' => $id, 'user_id' => $userId])
            ->asArray()
            ->one();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadAllOwned(
        int $userId,
        ?int $categoryId,
        int $limit,
        int $offset,
    ): array {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->ownedQuery($userId, $categoryId)
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->asArray()
            ->all();

        return $rows;
    }

    private function loadCountOwned(int $userId, ?int $categoryId): int
    {
        return (int) $this->ownedQuery($userId, $categoryId)->count();
    }

    /** @return ActiveQuery<Note> */
    private function ownedQuery(int $userId, ?int $categoryId): ActiveQuery
    {
        $query = Note::find()->where(['user_id' => $userId]);
        if ($categoryId !== null) {
            $query->andWhere(['category_id' => $categoryId]);
        }

        return $query;
    }

    /**
     * @param array<string|int> $key
     * @param Closure(): mixed $loader
     * @param list<string> $tags
     * @param Closure(mixed): bool $validator
     */
    private function remember(
        array $key,
        int $userId,
        Closure $loader,
        array $tags,
        Closure $validator,
    ): mixed {
        $generation = $this->generation($userId);
        if ($generation === null) {
            return $loader();
        }

        $key[] = 'generation';
        $key[] = $generation;

        try {
            $cached = $this->cache->get($key);
            if ($cached !== false && $validator($cached)) {
                return $cached;
            }

            if ($cached !== false) {
                $this->cache->delete($key);
                $this->logger->warning('notes.cache.invalid_value', [
                    'key_hash' => hash('sha256', serialize($key)),
                ]);
            }
        } catch (Throwable $exception) {
            $this->logCacheFailure('notes.cache.read_failed', $exception);
        }

        $value = $loader();

        if ($this->generation($userId) !== $generation) {
            $this->logger->info('notes.cache.stale_fill_skipped', ['user_id' => $userId]);

            return $value;
        }

        try {
            $stored = $this->cache->set(
                $key,
                $value,
                $this->cacheTtl,
                new TagDependency(['tags' => $tags]),
            );

            if (!$stored) {
                $this->logger->warning('notes.cache.write_failed');
            }
        } catch (Throwable $exception) {
            $this->logCacheFailure('notes.cache.write_failed', $exception);
        }

        return $value;
    }

    private function invalidateUserCache(int $userId): void
    {
        try {
            if (
                !$this->cache->set(
                    NoteCacheTags::generationKey($userId),
                    bin2hex(random_bytes(16)),
                    0,
                )
            ) {
                $this->logger->warning('notes.cache.generation_write_failed', [
                    'user_id' => $userId,
                ]);
            }
            TagDependency::invalidate($this->cache, NoteCacheTags::user($userId));
        } catch (Throwable $exception) {
            $this->logCacheFailure('notes.cache.invalidation_failed', $exception);
        }
    }

    private function generation(int $userId): ?string
    {
        $key = NoteCacheTags::generationKey($userId);

        try {
            $generation = $this->cache->get($key);
            if (is_string($generation) && $generation !== '') {
                return $generation;
            }

            $candidate = bin2hex(random_bytes(16));
            if ($this->cache->add($key, $candidate, 0)) {
                return $candidate;
            }

            $generation = $this->cache->get($key);
            if (is_string($generation) && $generation !== '') {
                return $generation;
            }

            $this->logger->warning('notes.cache.generation_read_failed', [
                'user_id' => $userId,
            ]);
        } catch (Throwable $exception) {
            $this->logCacheFailure('notes.cache.generation_read_failed', $exception);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private static function hydrate(array $attributes): Note
    {
        $note = Note::instantiate($attributes);
        Note::populateRecord($note, $attributes);

        return $note;
    }

    private function assertPagination(int $limit, int $offset): void
    {
        if ($limit < 1 || $offset < 0) {
            throw new InvalidArgumentException('Limit must be positive and offset must not be negative.');
        }
    }

    private static function positiveInt(mixed $value): ?int
    {
        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private static function isNoteRow(mixed $value): bool
    {
        return is_array($value)
            && isset($value['id'], $value['user_id'], $value['category_id'])
            && array_key_exists('title', $value)
            && array_key_exists('content', $value);
    }

    private static function isOwnedNoteRow(mixed $value, int $id, int $userId): bool
    {
        return self::isNoteRow($value)
            && (int) $value['id'] === $id
            && (int) $value['user_id'] === $userId;
    }

    /**
     * @param array<mixed> $values
     */
    private static function containsOnlyOwnedNoteRows(
        array $values,
        int $userId,
        ?int $categoryId,
    ): bool {
        foreach ($values as $value) {
            if (
                !self::isNoteRow($value)
                || (int) $value['user_id'] !== $userId
                || ($categoryId !== null && (int) $value['category_id'] !== $categoryId)
            ) {
                return false;
            }
        }

        return true;
    }

    private function logCacheFailure(string $event, Throwable $exception): void
    {
        $this->logger->warning($event, [
            'exception' => $exception::class,
        ]);
    }
}

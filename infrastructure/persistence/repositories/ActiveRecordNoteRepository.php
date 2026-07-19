<?php

declare(strict_types=1);

namespace infrastructure\persistence\repositories;

use Closure;
use domain\entities\Note;
use domain\exceptions\PersistenceException;
use domain\mappers\NoteDataMapperInterface;
use domain\repositories\NoteRepositoryInterface;
use domain\services\EventLoggerInterface;
use infrastructure\caching\NoteCacheTags;
use infrastructure\persistence\records\NoteRecord;
use InvalidArgumentException;
use Throwable;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;

final readonly class ActiveRecordNoteRepository implements NoteRepositoryInterface
{
    public function __construct(
        private NoteDataMapperInterface $mapper,
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
            $result = $this->remember(
                $key,
                fn (): ?Note => $this->loadOwnedById($id, $userId),
                [NoteCacheTags::user($userId)],
                static fn (mixed $value): bool => $value === null || $value instanceof Note,
            );

            return $result instanceof Note ? $result : null;
        } catch (PersistenceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->failure('Unable to find the note.', $exception);
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
            $result = $this->remember(
                $key,
                fn (): array => $this->loadAllOwned($userId, $categoryId, $limit, $offset),
                $this->queryTags($userId, $categoryId),
                static fn (mixed $value): bool => is_array($value)
                    && self::containsOnlyNotes($value),
            );

            if (!is_array($result)) {
                throw new PersistenceException('The notes cache returned an unexpected value.');
            }

            /** @var list<Note> $result */
            return $result;
        } catch (PersistenceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->failure('Unable to list notes.', $exception);
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
            $result = $this->remember(
                $key,
                fn (): int => $this->loadCountOwned($userId, $categoryId),
                $this->queryTags($userId, $categoryId),
                static fn (mixed $value): bool => is_int($value) && $value >= 0,
            );

            if (!is_int($result)) {
                throw new PersistenceException('The notes count cache returned an unexpected value.');
            }

            return $result;
        } catch (PersistenceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->failure('Unable to count notes.', $exception);
        }
    }

    public function save(Note $note): Note
    {
        try {
            $data = $this->mapper->toArray($note);
            $record = $note->id === null
                ? new NoteRecord()
                : NoteRecord::findOne([
                    'id' => $note->id,
                    'user_id' => $note->userId,
                ]);

            if (!$record instanceof NoteRecord) {
                throw new PersistenceException('Cannot update a note that does not exist or is not owned by the user.');
            }

            $oldUserId = $record->getIsNewRecord() ? null : (int) $record->user_id;
            $oldCategoryId = $record->getIsNewRecord() ? null : (int) $record->category_id;

            $record->setAttributes([
                'user_id' => $data['user_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'] ?? null,
                'content' => $data['content'] ?? null,
            ], false);

            if (!$record->save()) {
                throw new PersistenceException($this->validationFailure('Unable to save the note.', $record));
            }

            if (!$record->refresh()) {
                throw new PersistenceException('The note was saved but could not be reloaded.');
            }

            $saved = $this->map($record->getAttributes());
            $this->invalidateAfterWrite(
                $saved->userId,
                $saved->categoryId,
                $oldUserId,
                $oldCategoryId,
            );

            return $saved;
        } catch (PersistenceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->failure('Unable to save the note.', $exception);
        }
    }

    public function delete(Note $note): void
    {
        $id = $note->id;
        if ($id === null) {
            throw new PersistenceException('Cannot delete a note without an id.');
        }

        try {
            $record = NoteRecord::findOne([
                'id' => $id,
                'user_id' => $note->userId,
            ]);

            if (!$record instanceof NoteRecord) {
                throw new PersistenceException('Cannot delete a note that does not exist or is not owned by the user.');
            }

            $categoryId = (int) $record->category_id;
            if ($record->delete() !== 1) {
                throw new PersistenceException('The note could not be deleted.');
            }

            $this->invalidateAfterWrite($note->userId, $categoryId);
        } catch (PersistenceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->failure('Unable to delete the note.', $exception);
        }
    }

    private function loadOwnedById(int $id, int $userId): ?Note
    {
        $data = NoteRecord::find()
            ->where(['id' => $id, 'user_id' => $userId])
            ->asArray()
            ->one();

        return is_array($data) ? $this->map($data) : null;
    }

    /**
     * @return list<Note>
     */
    private function loadAllOwned(
        int $userId,
        ?int $categoryId,
        int $limit,
        int $offset,
    ): array {
        $query = NoteRecord::find()->where(['user_id' => $userId]);
        if ($categoryId !== null) {
            $query->andWhere(['category_id' => $categoryId]);
        }

        $rows = $query
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->asArray()
            ->all();

        return array_map(fn (array $row): Note => $this->map($row), $rows);
    }

    private function loadCountOwned(int $userId, ?int $categoryId): int
    {
        $query = NoteRecord::find()->where(['user_id' => $userId]);
        if ($categoryId !== null) {
            $query->andWhere(['category_id' => $categoryId]);
        }

        return (int) $query->count();
    }

    /**
     * @param array<string|int> $key
     * @param Closure(): mixed $loader
     * @param list<string> $tags
     * @param Closure(mixed): bool $validator
     */
    private function remember(array $key, Closure $loader, array $tags, Closure $validator): mixed
    {
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

    private function invalidateAfterWrite(
        int $userId,
        int $categoryId,
        ?int $oldUserId = null,
        ?int $oldCategoryId = null,
    ): void {
        $tags = $this->queryTags($userId, $categoryId);
        if ($oldUserId !== null) {
            $tags[] = NoteCacheTags::user($oldUserId);
        }
        if ($oldCategoryId !== null) {
            $tags[] = NoteCacheTags::category($oldUserId ?? $userId, $oldCategoryId);
        }

        try {
            TagDependency::invalidate($this->cache, array_values(array_unique($tags)));
        } catch (Throwable $exception) {
            $this->logCacheFailure('notes.cache.invalidation_failed', $exception);
        }
    }

    /**
     * @return list<string>
     */
    private function queryTags(int $userId, ?int $categoryId): array
    {
        $tags = [NoteCacheTags::user($userId)];
        if ($categoryId !== null) {
            $tags[] = NoteCacheTags::category($userId, $categoryId);
        }

        return $tags;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function map(array $data): Note
    {
        return $this->mapper->fromArray($data);
    }

    private function assertPagination(int $limit, int $offset): void
    {
        if ($limit < 1 || $offset < 0) {
            throw new InvalidArgumentException('Limit must be positive and offset must not be negative.');
        }
    }

    /**
     * @param array<mixed> $values
     */
    private static function containsOnlyNotes(array $values): bool
    {
        foreach ($values as $value) {
            if (!$value instanceof Note) {
                return false;
            }
        }

        return true;
    }

    private function validationFailure(string $message, NoteRecord $record): string
    {
        $attributes = array_keys($record->getErrors());

        return $attributes === []
            ? $message
            : sprintf('%s Invalid attributes: %s.', $message, implode(', ', $attributes));
    }

    private function failure(string $message, Throwable $exception): PersistenceException
    {
        return $exception instanceof PersistenceException
            ? $exception
            : new PersistenceException($message, 0, $exception);
    }

    private function logCacheFailure(string $event, Throwable $exception): void
    {
        $this->logger->warning($event, [
            'exception' => $exception::class,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace backend\services;

use backend\forms\NoteForm;
use domain\services\EventLoggerInterface;
use infrastructure\caching\NoteCacheTags;
use infrastructure\persistence\records\NoteRecord;
use Throwable;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;

final readonly class NoteManagementService
{
    public function __construct(
        private CacheInterface $cache,
        private EventLoggerInterface $logger,
        private RecordDeleter $recordDeleter,
    ) {
    }

    public function update(NoteRecord $note, NoteForm $form): bool
    {
        $note->category_id = (int) $form->category_id;
        $note->title = (string) $form->title;
        $note->content = (string) $form->content;

        if (!$note->save()) {
            $form->copyErrorsFrom($note);

            return false;
        }

        $this->invalidateUserCache((int) $note->user_id);
        $this->logger->info('note.updated.admin', ['note_id' => (int) $note->id]);

        return true;
    }

    public function delete(NoteRecord $note): void
    {
        $userId = (int) $note->user_id;

        $this->recordDeleter->delete($note);

        $this->invalidateUserCache($userId);
        $this->logger->info('note.deleted.admin', ['note_id' => (int) $note->id]);
    }

    private function invalidateUserCache(int $userId): void
    {
        if ($userId < 1) {
            return;
        }

        try {
            TagDependency::invalidate($this->cache, NoteCacheTags::user($userId));
        } catch (Throwable $exception) {
            $this->logger->warning('notes.cache.admin_invalidation_failed', [
                'exception_class' => $exception::class,
                'user_id' => $userId,
            ]);
        }
    }
}

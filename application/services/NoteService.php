<?php

declare(strict_types=1);

namespace application\services;

use application\dto\note\CreateNoteDto;
use application\dto\note\NoteQueryDto;
use application\dto\note\UpdateNoteDto;
use application\results\NotePage;
use domain\entities\Note;
use domain\exceptions\NotFoundException;
use domain\repositories\CategoryRepositoryInterface;
use domain\repositories\NoteRepositoryInterface;
use domain\services\EventLoggerInterface;

final readonly class NoteService
{
    public function __construct(
        private NoteRepositoryInterface $notes,
        private CategoryRepositoryInterface $categories,
        private EventLoggerInterface $logger,
    ) {
    }

    public function list(int $userId, NoteQueryDto $query): NotePage
    {
        $categoryId = $query->categoryId();

        if ($categoryId !== null) {
            $this->assertCategoryExists($categoryId);
        }

        $page = min(NoteQueryDto::MAX_PAGE, max(1, $query->pageNumber()));
        $perPage = min(100, max(1, $query->perPage()));
        $offset = ($page - 1) * $perPage;
        $total = $this->notes->countOwned($userId, $categoryId);
        $items = $this->notes->findAllOwned($userId, $categoryId, $perPage, $offset);

        $this->logger->info('notes.listed', [
            'user_id' => $userId,
            'category_id' => $categoryId,
            'page' => $page,
            'per_page' => $perPage,
            'returned' => count($items),
            'total' => $total,
        ]);

        return new NotePage($items, $total, $page, $perPage);
    }

    public function get(int $userId, int $noteId): Note
    {
        $note = $this->findOwnedOrFail($noteId, $userId);

        $this->logger->info('note.viewed', [
            'user_id' => $userId,
            'note_id' => $noteId,
        ]);

        return $note;
    }

    public function create(int $userId, CreateNoteDto $dto): Note
    {
        $categoryId = $dto->categoryId();
        $this->assertCategoryExists($categoryId);

        $note = new Note(
            id: null,
            userId: $userId,
            categoryId: $categoryId,
            title: $dto->titleValue(),
            content: $dto->contentValue(),
        );
        $savedNote = $this->notes->save($note);

        $this->logger->info('note.created', [
            'user_id' => $userId,
            'note_id' => $savedNote->id,
            'category_id' => $savedNote->categoryId,
        ]);

        return $savedNote;
    }

    public function update(int $userId, int $noteId, UpdateNoteDto $dto): Note
    {
        $currentNote = $this->findOwnedOrFail($noteId, $userId);
        $categoryId = $dto->categoryId();
        $this->assertCategoryExists($categoryId);

        $note = new Note(
            id: $currentNote->id,
            userId: $currentNote->userId,
            categoryId: $categoryId,
            title: $dto->titleValue(),
            content: $dto->contentValue(),
            createdAt: $currentNote->createdAt,
            updatedAt: $currentNote->updatedAt,
        );
        $savedNote = $this->notes->save($note);

        $this->logger->info('note.updated', [
            'user_id' => $userId,
            'note_id' => $noteId,
            'category_id' => $savedNote->categoryId,
        ]);

        return $savedNote;
    }

    public function delete(int $userId, int $noteId): void
    {
        $note = $this->findOwnedOrFail($noteId, $userId);
        $this->notes->delete($note);

        $this->logger->info('note.deleted', [
            'user_id' => $userId,
            'note_id' => $noteId,
        ]);
    }

    private function findOwnedOrFail(int $noteId, int $userId): Note
    {
        $note = $this->notes->findOwnedById($noteId, $userId);

        if ($note !== null) {
            return $note;
        }

        $this->logger->warning('note.not_found', [
            'user_id' => $userId,
            'note_id' => $noteId,
        ]);

        throw new NotFoundException('Note not found.');
    }

    private function assertCategoryExists(int $categoryId): void
    {
        if ($this->categories->findById($categoryId) !== null) {
            return;
        }

        $this->logger->warning('category.not_found', [
            'category_id' => $categoryId,
        ]);

        throw new NotFoundException('Category not found.');
    }
}

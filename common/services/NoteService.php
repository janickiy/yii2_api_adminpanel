<?php

declare(strict_types=1);

namespace common\services;

use common\dtos\NotePageDto;
use common\dtos\NoteQueryDto;
use common\dtos\NoteWriteDto;
use common\entities\Note;
use common\repositories\CategoryRepositoryInterface;
use common\repositories\NoteRepositoryInterface;
use common\repositories\PersistenceException;
use common\services\exceptions\CategoryNotFoundException;
use common\services\exceptions\NotFoundException;
use yii\db\ActiveQuery;

final readonly class NoteService
{
    public function __construct(
        private NoteRepositoryInterface $notes,
        private CategoryRepositoryInterface $categories,
        private EventLoggerInterface $logger,
    ) {
    }

    public function list(int $userId, NoteQueryDto $query): NotePageDto
    {
        if ($query->categoryId !== null) {
            $this->assertCategoryExists($query->categoryId);
        }

        $offset = ($query->page - 1) * $query->perPage;
        $total = $this->notes->countOwned($userId, $query->categoryId);
        $items = $this->notes->findAllOwned(
            $userId,
            $query->categoryId,
            $query->perPage,
            $offset,
        );

        $this->logger->info('notes.listed', [
            'user_id' => $userId,
            'category_id' => $query->categoryId,
            'page' => $query->page,
            'per_page' => $query->perPage,
            'returned' => count($items),
            'total' => $total,
        ]);

        return new NotePageDto($items, $total, $query->page, $query->perPage);
    }

    public function get(int $userId, int $noteId): Note
    {
        $note = $this->findOwnedOrFail($noteId, $userId);
        $this->logger->info('note.viewed', ['user_id' => $userId, 'note_id' => $noteId]);

        return $note;
    }

    public function create(int $userId, NoteWriteDto $dto): Note
    {
        $this->assertCategoryExists($dto->categoryId);
        $note = $this->persist(new Note([
            'user_id' => $userId,
            'category_id' => $dto->categoryId,
            'title' => $dto->title,
            'content' => $dto->content,
        ]));

        $this->logger->info('note.created', [
            'user_id' => $userId,
            'note_id' => (int) $note->id,
            'category_id' => (int) $note->category_id,
        ]);

        return $note;
    }

    public function update(int $userId, int $noteId, NoteWriteDto $dto): Note
    {
        $note = $this->findOwnedOrFail($noteId, $userId);
        $this->assertCategoryExists($dto->categoryId);
        $this->apply($note, $dto);
        $note = $this->persist($note);

        $this->logger->info('note.updated', [
            'user_id' => $userId,
            'note_id' => $noteId,
            'category_id' => (int) $note->category_id,
        ]);

        return $note;
    }

    public function delete(int $userId, int $noteId): void
    {
        $note = $this->findOwnedOrFail($noteId, $userId);
        $this->notes->delete($note);
        $this->logger->info('note.deleted', ['user_id' => $userId, 'note_id' => $noteId]);
    }

    /** @return ActiveQuery<Note> */
    public function query(): ActiveQuery
    {
        return $this->notes->query();
    }

    public function count(): int
    {
        return $this->notes->count();
    }

    public function find(int $id): Note
    {
        $note = $this->notes->findById($id);
        if (!$note instanceof Note) {
            throw new NotFoundException('Note not found.');
        }

        return $note;
    }

    public function updateRecord(Note $note, NoteWriteDto $dto): Note
    {
        $this->assertCategoryExists($dto->categoryId);
        $this->apply($note, $dto);
        $note = $this->persist($note);
        $this->logger->info('note.updated.admin', ['note_id' => (int) $note->id]);

        return $note;
    }

    public function deleteRecord(Note $note): void
    {
        $noteId = (int) $note->id;
        $this->notes->delete($note);
        $this->logger->info('note.deleted.admin', ['note_id' => $noteId]);
    }

    private function findOwnedOrFail(int $noteId, int $userId): Note
    {
        $note = $this->notes->findOwnedById($noteId, $userId);
        if ($note instanceof Note) {
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

        $this->throwCategoryNotFound($categoryId);
    }

    private function persist(Note $note): Note
    {
        try {
            return $this->notes->save($note);
        } catch (PersistenceException $exception) {
            $categoryId = (int) $note->category_id;
            if ($this->categories->findById($categoryId) === null) {
                $this->throwCategoryNotFound($categoryId, $exception);
            }

            throw $exception;
        }
    }

    private function throwCategoryNotFound(
        int $categoryId,
        ?PersistenceException $previous = null,
    ): never {
        $this->logger->warning('category.not_found', ['category_id' => $categoryId]);

        throw new CategoryNotFoundException('Category not found.', 0, $previous);
    }

    private function apply(Note $note, NoteWriteDto $dto): void
    {
        $note->category_id = $dto->categoryId;
        $note->title = $dto->title;
        $note->content = $dto->content;
    }
}

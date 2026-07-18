<?php

declare(strict_types=1);

namespace domain\repositories;

use domain\entities\Note;

interface NoteRepositoryInterface
{
    public function findOwnedById(int $id, int $userId): ?Note;

    /**
     * @return list<Note>
     */
    public function findAllOwned(
        int $userId,
        ?int $categoryId,
        int $limit,
        int $offset,
    ): array;

    public function countOwned(int $userId, ?int $categoryId): int;

    public function save(Note $note): Note;

    public function delete(Note $note): void;
}

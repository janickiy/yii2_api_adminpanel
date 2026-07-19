<?php

declare(strict_types=1);

namespace common\repositories;

use common\entities\Note;
use yii\db\ActiveQuery;

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

    public function findById(int $id): ?Note;

    /** @return ActiveQuery<Note> */
    public function query(): ActiveQuery;

    public function count(): int;

    public function save(Note $note): Note;

    public function delete(Note $note): void;
}

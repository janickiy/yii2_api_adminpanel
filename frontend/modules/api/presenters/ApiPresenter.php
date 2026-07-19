<?php

declare(strict_types=1);

namespace frontend\modules\api\presenters;

use domain\entities\Category;
use domain\entities\Note;
use domain\entities\User;

final readonly class ApiPresenter
{
    /** @return array<string, mixed> */
    public function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->createdAt?->format(DATE_ATOM),
            'updated_at' => $user->updatedAt?->format(DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function note(Note $note): array
    {
        return [
            'id' => $note->id,
            'user_id' => $note->userId,
            'category_id' => $note->categoryId,
            'title' => $note->title,
            'content' => $note->content,
            'created_at' => $note->createdAt?->format(DATE_ATOM),
            'updated_at' => $note->updatedAt?->format(DATE_ATOM),
        ];
    }

    /** @return array{id: int, name: string} */
    public function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
        ];
    }
}

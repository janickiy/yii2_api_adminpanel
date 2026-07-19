<?php

declare(strict_types=1);

namespace frontend\components\api;

use common\entities\Category;
use common\entities\Note;
use common\entities\User;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final readonly class ApiPresenter
{
    /** @return array<string, mixed> */
    public function user(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $this->dateTime($user->created_at),
            'updated_at' => $this->dateTime($user->updated_at),
        ];
    }

    /** @return array<string, mixed> */
    public function note(Note $note): array
    {
        return [
            'id' => (int) $note->id,
            'user_id' => (int) $note->user_id,
            'category_id' => (int) $note->category_id,
            'title' => $note->title,
            'content' => $note->content,
            'created_at' => $this->dateTime($note->created_at),
            'updated_at' => $this->dateTime($note->updated_at),
        ];
    }

    /** @return array{id: int, name: string} */
    public function category(Category $category): array
    {
        return [
            'id' => (int) $category->id,
            'name' => $category->name,
        ];
    }

    private function dateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $utc = new DateTimeZone('UTC');
        $dateTime = $value instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($value)
            : new DateTimeImmutable((string) $value, $utc);

        return $dateTime->setTimezone($utc)->format(DATE_ATOM);
    }
}

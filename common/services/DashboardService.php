<?php

declare(strict_types=1);

namespace common\services;

use common\repositories\AdminRepositoryInterface;
use common\repositories\CategoryRepositoryInterface;
use common\repositories\MessageRepositoryInterface;
use common\repositories\NoteRepositoryInterface;
use common\repositories\UserRepositoryInterface;

final readonly class DashboardService
{
    public function __construct(
        private NoteRepositoryInterface $notes,
        private CategoryRepositoryInterface $categories,
        private MessageRepositoryInterface $messages,
        private UserRepositoryInterface $users,
        private AdminRepositoryInterface $admins,
    ) {
    }

    /**
     * @return array{notes: int, categories: int, newMessages: int, users: int|null, admins: int|null}
     */
    public function counts(bool $includeRestricted): array
    {
        return [
            'notes' => $this->notes->count(),
            'categories' => $this->categories->count(),
            'newMessages' => $this->messages->countNew(),
            'users' => $includeRestricted ? $this->users->count() : null,
            'admins' => $includeRestricted ? $this->admins->count() : null,
        ];
    }
}

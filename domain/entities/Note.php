<?php

declare(strict_types=1);

namespace domain\entities;

use DateTimeImmutable;

final readonly class Note
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public int $categoryId,
        public string $title,
        public string $content,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {
    }
}

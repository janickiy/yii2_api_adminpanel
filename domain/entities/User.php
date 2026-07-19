<?php

declare(strict_types=1);

namespace domain\entities;

use DateTimeImmutable;

final readonly class User
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {
    }
}

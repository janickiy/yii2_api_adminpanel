<?php

declare(strict_types=1);

namespace domain\entities;

final readonly class Category
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

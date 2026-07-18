<?php

declare(strict_types=1);

namespace domain\services;

interface PasswordHasherInterface
{
    public function hash(string $password): string;

    public function verify(string $password, string $passwordHash): bool;
}

<?php

declare(strict_types=1);

namespace common\repositories;

interface RevokedTokenRepositoryInterface
{
    /** @phpstan-impure */
    public function isRevoked(string $jti): bool;

    public function revoke(string $jti, int $expiresAt): void;

    public function deleteExpired(): int;
}

<?php

declare(strict_types=1);

namespace common\repositories;

final class NoteCacheTags
{
    public const NAMESPACE = 'notes:v1';

    public static function user(int $userId): string
    {
        return sprintf('%s:user:%d', self::NAMESPACE, $userId);
    }

    /** @return array{string, string, string, int} */
    public static function generationKey(int $userId): array
    {
        return [self::NAMESPACE, 'generation', 'user', $userId];
    }
}

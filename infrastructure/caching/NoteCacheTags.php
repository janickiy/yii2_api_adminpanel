<?php

declare(strict_types=1);

namespace infrastructure\caching;

final class NoteCacheTags
{
    public const NAMESPACE = 'notes:v1';

    public static function user(int $userId): string
    {
        return sprintf('%s:user:%d', self::NAMESPACE, $userId);
    }

    public static function category(int $userId, int $categoryId): string
    {
        return sprintf('%s:user:%d:category:%d', self::NAMESPACE, $userId, $categoryId);
    }
}

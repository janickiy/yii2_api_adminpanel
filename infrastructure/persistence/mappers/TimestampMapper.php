<?php

declare(strict_types=1);

namespace infrastructure\persistence\mappers;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class TimestampMapper
{
    public static function fromStorage(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value->setTimezone(self::utc());
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)->setTimezone(self::utc());
        }

        return (new DateTimeImmutable((string) $value, self::utc()))->setTimezone(self::utc());
    }

    public static function toStorage(?DateTimeImmutable $value): ?string
    {
        return $value?->setTimezone(self::utc())->format('Y-m-d H:i:s');
    }

    private static function utc(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }
}

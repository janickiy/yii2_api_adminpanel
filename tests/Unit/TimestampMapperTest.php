<?php

declare(strict_types=1);

namespace tests\Unit;

use DateTimeImmutable;
use infrastructure\persistence\mappers\TimestampMapper;
use PHPUnit\Framework\TestCase;

final class TimestampMapperTest extends TestCase
{
    public function testZoneLessPostgresTimestampIsInterpretedAsUtc(): void
    {
        $timestamp = TimestampMapper::fromStorage('2026-07-18 23:00:00');

        self::assertNotNull($timestamp);
        self::assertSame('2026-07-18T23:00:00+00:00', $timestamp->format(DATE_ATOM));
    }

    public function testTimestampIsStoredAsUtcWithoutAZoneSuffix(): void
    {
        $timestamp = new DateTimeImmutable('2026-07-18T23:00:00+03:00');

        self::assertSame('2026-07-18 20:00:00', TimestampMapper::toStorage($timestamp));
    }
}

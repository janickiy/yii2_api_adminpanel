<?php

declare(strict_types=1);

namespace tests\Unit;

use infrastructure\caching\NoteCacheTags;
use infrastructure\persistence\records\CategoryRecord;
use infrastructure\persistence\records\NoteRecord;
use infrastructure\persistence\records\UserRecord;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class PersistenceRecordsTest extends TestCase
{
    public function testRelationsHaveSingleCanonicalOwner(): void
    {
        self::assertSame(
            CategoryRecord::class,
            (new ReflectionMethod(CategoryRecord::class, 'getNotes'))->getDeclaringClass()->getName(),
        );
        self::assertSame(
            NoteRecord::class,
            (new ReflectionMethod(NoteRecord::class, 'getCategory'))->getDeclaringClass()->getName(),
        );
        self::assertSame(
            UserRecord::class,
            (new ReflectionMethod(UserRecord::class, 'getNotes'))->getDeclaringClass()->getName(),
        );
    }

    public function testLegacyActiveRecordAliasesAreRemoved(): void
    {
        self::assertFalse(class_exists('common\\models\\Catalog'));
        self::assertFalse(class_exists('common\\models\\Notes'));
    }

    public function testNoteCacheTagsHaveOneCanonicalGenerator(): void
    {
        self::assertSame('notes:v1:user:7', NoteCacheTags::user(7));
        self::assertSame('notes:v1:user:7:category:3', NoteCacheTags::category(7, 3));
    }
}

<?php

declare(strict_types=1);

namespace tests\Unit;

use application\dto\note\NoteQueryDto;
use PHPUnit\Framework\TestCase;

final class NoteQueryDtoTest extends TestCase
{
    public function testRejectsPageThatCouldProduceAnUnsafeOffset(): void
    {
        $query = new NoteQueryDto([
            'page' => (string) PHP_INT_MAX,
            'per_page' => 100,
        ]);

        self::assertFalse($query->validate());
        self::assertArrayHasKey('page', $query->getErrors());
    }
}

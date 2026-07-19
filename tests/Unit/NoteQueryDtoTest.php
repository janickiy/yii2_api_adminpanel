<?php

declare(strict_types=1);

namespace tests\Unit;

use application\dto\note\NoteQueryDto;
use frontend\modules\api\http\input\NoteQueryInput;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NoteQueryDtoTest extends TestCase
{
    public function testRejectsPageThatCouldProduceAnUnsafeOffset(): void
    {
        $query = new NoteQueryInput([
            'page' => (string) PHP_INT_MAX,
            'per_page' => 100,
        ]);

        self::assertFalse($query->validate());
        self::assertArrayHasKey('page', $query->getErrors());
    }

    public function testMapsValidatedTransportNamesToApplicationDto(): void
    {
        $query = new NoteQueryInput([
            'category_id' => ' 7 ',
            'page' => ' 3 ',
            'per_page' => ' 40 ',
        ]);

        self::assertTrue($query->validate());

        $dto = $query->toDto();
        self::assertSame(7, $dto->categoryId);
        self::assertSame(3, $dto->page);
        self::assertSame(40, $dto->perPage);
    }

    public function testApplicationDtoRejectsUnsafePaginationFromAnyAdapter(): void
    {
        foreach (
            [
                [null, 0, 20],
                [null, NoteQueryDto::MAX_PAGE + 1, 20],
                [null, 1, 0],
                [null, 1, NoteQueryDto::MAX_PER_PAGE + 1],
                [0, 1, 20],
            ] as [$categoryId, $page, $perPage]
        ) {
            try {
                new NoteQueryDto($categoryId, $page, $perPage);
                self::fail('Invalid pagination must not cross the application boundary.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}

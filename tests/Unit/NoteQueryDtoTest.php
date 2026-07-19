<?php

declare(strict_types=1);

namespace tests\Unit;

use common\dtos\NoteQueryDto;
use frontend\forms\api\NoteQueryInput;
use PHPUnit\Framework\TestCase;

final class NoteQueryDtoTest extends TestCase
{
    public function testInputRejectsPageThatCouldProduceAnUnsafeOffset(): void
    {
        $query = new NoteQueryInput([
            'page' => (string) PHP_INT_MAX,
            'per_page' => 100,
        ]);

        self::assertFalse($query->validate());
        self::assertArrayHasKey('page', $query->getErrors());
    }

    public function testInputRejectsInvalidCategoryAndPageSize(): void
    {
        $query = new NoteQueryInput([
            'category_id' => 0,
            'page' => 1,
            'per_page' => 101,
        ]);

        self::assertFalse($query->validate());
        self::assertArrayHasKey('category_id', $query->getErrors());
        self::assertArrayHasKey('per_page', $query->getErrors());
    }

    public function testMapsValidatedTransportNamesToDto(): void
    {
        $query = new NoteQueryInput([
            'category_id' => ' 7 ',
            'page' => ' 3 ',
            'per_page' => ' 40 ',
        ]);

        self::assertTrue($query->validate());

        $dto = $query->toDto();
        self::assertInstanceOf(NoteQueryDto::class, $dto);
        self::assertSame(7, $dto->categoryId);
        self::assertSame(3, $dto->page);
        self::assertSame(40, $dto->perPage);
    }

    public function testDtoOnlyCarriesAlreadyValidatedValues(): void
    {
        $dto = new NoteQueryDto(categoryId: 5, page: 2, perPage: 25);

        self::assertSame(5, $dto->categoryId);
        self::assertSame(2, $dto->page);
        self::assertSame(25, $dto->perPage);
    }
}

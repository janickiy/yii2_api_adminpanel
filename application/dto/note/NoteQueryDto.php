<?php

declare(strict_types=1);

namespace application\dto\note;

use InvalidArgumentException;

final readonly class NoteQueryDto
{
    public const MAX_PAGE = 1_000_000;
    public const MAX_PER_PAGE = 100;

    public function __construct(
        public ?int $categoryId = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
        if ($categoryId !== null && $categoryId < 1) {
            throw new InvalidArgumentException('Category id must be positive.');
        }
        if ($page < 1 || $page > self::MAX_PAGE) {
            throw new InvalidArgumentException('Page is outside the supported range.');
        }
        if ($perPage < 1 || $perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException('Page size is outside the supported range.');
        }
    }
}

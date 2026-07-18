<?php

declare(strict_types=1);

namespace application\dto\note;

use application\dto\BaseDto;

final class NoteQueryDto extends BaseDto
{
    public const MAX_PAGE = 1_000_000;

    public mixed $category_id = null;
    public mixed $page = 1;
    public mixed $per_page = 20;

    public function rules(): array
    {
        $trim = static fn (mixed $value): mixed => is_string($value) ? trim($value) : $value;

        return [
            [['category_id', 'page', 'per_page'], 'filter', 'filter' => $trim],
            ['category_id', 'default', 'value' => null],
            ['page', 'default', 'value' => 1],
            ['per_page', 'default', 'value' => 20],
            ['category_id', 'integer', 'min' => 1],
            ['page', 'integer', 'min' => 1, 'max' => self::MAX_PAGE],
            ['per_page', 'integer', 'min' => 1, 'max' => 100],
        ];
    }

    public function categoryId(): ?int
    {
        if ($this->category_id === null || $this->category_id === '') {
            return null;
        }

        return (int) $this->category_id;
    }

    public function pageNumber(): int
    {
        return (int) $this->page;
    }

    public function perPage(): int
    {
        return (int) $this->per_page;
    }
}

<?php

declare(strict_types=1);

namespace frontend\modules\api\forms;

use common\dtos\NoteQueryDto;

final class NoteQueryInput extends RequestInput
{
    private const MAX_PAGE = 1_000_000;
    private const MAX_PER_PAGE = 100;

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
            ['per_page', 'integer', 'min' => 1, 'max' => self::MAX_PER_PAGE],
        ];
    }

    public function toDto(): NoteQueryDto
    {
        $categoryId = $this->category_id === null || $this->category_id === ''
            ? null
            : (int) $this->category_id;

        return new NoteQueryDto(
            categoryId: $categoryId,
            page: (int) $this->page,
            perPage: (int) $this->per_page,
        );
    }
}

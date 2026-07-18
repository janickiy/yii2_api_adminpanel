<?php

declare(strict_types=1);

namespace application\dto\note;

use application\dto\BaseDto;

final class UpdateNoteDto extends BaseDto
{
    public mixed $category_id = null;
    public mixed $title = null;
    public mixed $content = null;

    public function rules(): array
    {
        return [
            [['title', 'content'], 'trim'],
            ['category_id', 'filter', 'filter' => static fn (mixed $value): mixed => is_string($value)
                ? trim($value)
                : $value],
            [['category_id', 'title', 'content'], 'required'],
            ['category_id', 'integer', 'min' => 1],
            ['title', 'string', 'max' => 255],
            ['content', 'string'],
        ];
    }

    public function categoryId(): int
    {
        return (int) $this->category_id;
    }

    public function titleValue(): string
    {
        return trim((string) $this->title);
    }

    public function contentValue(): string
    {
        return trim((string) $this->content);
    }
}

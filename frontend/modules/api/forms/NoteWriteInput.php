<?php

declare(strict_types=1);

namespace frontend\modules\api\forms;

use common\dtos\NoteWriteDto;

final class NoteWriteInput extends RequestInput
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

    public function toDto(): NoteWriteDto
    {
        return new NoteWriteDto(
            categoryId: (int) $this->category_id,
            title: (string) $this->title,
            content: (string) $this->content,
        );
    }
}

<?php

declare(strict_types=1);

namespace common\dtos;

final class NoteWriteDto
{
    public int $categoryId;
    public string $title;
    public string $content;

    public function __construct(
        int $categoryId,
        string $title,
        string $content,
    ) {
        $this->categoryId = $categoryId;
        $this->title = $title;
        $this->content = $content;
    }
}

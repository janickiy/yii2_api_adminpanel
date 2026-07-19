<?php

declare(strict_types=1);

namespace application\dto\note;

use InvalidArgumentException;

final readonly class NoteWriteDto
{
    public int $categoryId;
    public string $title;
    public string $content;

    public function __construct(
        int $categoryId,
        string $title,
        string $content,
    ) {
        $title = trim($title);
        $content = trim($content);

        if ($categoryId < 1) {
            throw new InvalidArgumentException('Category id must be positive.');
        }
        if ($title === '' || mb_strlen($title) > 255) {
            throw new InvalidArgumentException('Note title must contain between 1 and 255 characters.');
        }
        if ($content === '') {
            throw new InvalidArgumentException('Note content must not be empty.');
        }

        $this->categoryId = $categoryId;
        $this->title = $title;
        $this->content = $content;
    }
}

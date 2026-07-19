<?php

declare(strict_types=1);

namespace common\dtos;

final class NoteQueryDto
{
    public ?int $categoryId;
    public int $page;
    public int $perPage;

    public function __construct(
        ?int $categoryId = null,
        int $page = 1,
        int $perPage = 20,
    ) {
        $this->categoryId = $categoryId;
        $this->page = $page;
        $this->perPage = $perPage;
    }
}

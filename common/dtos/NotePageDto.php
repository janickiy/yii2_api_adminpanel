<?php

declare(strict_types=1);

namespace common\dtos;

use common\entities\Note;

final class NotePageDto
{
    /** @var list<Note> */
    public array $items;
    public int $total;
    public int $page;
    public int $perPage;

    /**
     * @param list<Note> $items
     */
    public function __construct(
        array $items,
        int $total,
        int $page,
        int $perPage,
    ) {
        $this->items = $items;
        $this->total = $total;
        $this->page = $page;
        $this->perPage = $perPage;
    }
}

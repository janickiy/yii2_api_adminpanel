<?php

declare(strict_types=1);

namespace application\results;

use domain\entities\Note;

final readonly class NotePage
{
    /**
     * @param list<Note> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace domain\repositories;

use domain\entities\Category;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;

    /**
     * @return list<Category>
     */
    public function findAll(): array;
}

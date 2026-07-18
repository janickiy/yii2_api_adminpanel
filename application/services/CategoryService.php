<?php

declare(strict_types=1);

namespace application\services;

use domain\entities\Category;
use domain\repositories\CategoryRepositoryInterface;

final readonly class CategoryService
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
    ) {
    }

    /** @return list<Category> */
    public function list(): array
    {
        return $this->categories->findAll();
    }
}

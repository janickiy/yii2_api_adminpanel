<?php

declare(strict_types=1);

namespace common\repositories;

use common\entities\Category;
use yii\db\ActiveQuery;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;

    public function findByName(string $name): ?Category;

    /**
     * @return list<Category>
     */
    public function findAll(): array;

    /** @return ActiveQuery<Category> */
    public function query(): ActiveQuery;

    public function count(): int;

    public function save(Category $category): Category;

    public function delete(Category $category): void;
}

<?php

declare(strict_types=1);

namespace common\repositories;

use common\entities\Category;
use Throwable;
use yii\db\ActiveQuery;

final class CategoryRepository extends AbstractActiveRecordRepository implements CategoryRepositoryInterface
{
    public function findById(int $id): ?Category
    {
        try {
            $category = Category::findOne(['id' => $id]);

            return $category instanceof Category ? $category : null;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to find the category.', $exception);
        }
    }

    public function findAll(): array
    {
        try {
            /** @var list<Category> $categories */
            $categories = $this->query()->all();

            return $categories;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to list categories.', $exception);
        }
    }

    public function findByName(string $name): ?Category
    {
        try {
            $category = Category::find()->where(['name' => $name])->one();

            return $category instanceof Category ? $category : null;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to find the category by name.', $exception);
        }
    }

    public function query(): ActiveQuery
    {
        return Category::find()->orderBy(['name' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function count(): int
    {
        try {
            return (int) $this->query()->count();
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to count categories.', $exception);
        }
    }

    public function save(Category $category): Category
    {
        return $this->saveRecord($category, 'Unable to save the category.');
    }

    public function delete(Category $category): void
    {
        $this->deleteRecord($category, 'Unable to delete the category.');
    }
}

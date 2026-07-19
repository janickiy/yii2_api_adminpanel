<?php

declare(strict_types=1);

namespace common\services;

use common\dtos\CategoryWriteDto;
use common\entities\Category;
use common\repositories\CategoryRepositoryInterface;
use common\repositories\PersistenceException;
use common\services\exceptions\ConflictException;
use common\services\exceptions\NotFoundException;
use Throwable;
use yii\db\ActiveQuery;
use yii\db\IntegrityException;

final readonly class CategoryService
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
        private EventLoggerInterface $logger,
    ) {
    }

    /** @return list<Category> */
    public function list(): array
    {
        return $this->categories->findAll();
    }

    /** @return ActiveQuery<Category> */
    public function query(): ActiveQuery
    {
        return $this->categories->query();
    }

    public function count(): int
    {
        return $this->categories->count();
    }

    public function get(int $id): Category
    {
        $category = $this->categories->findById($id);
        if (!$category instanceof Category) {
            throw new NotFoundException('Category not found.');
        }

        return $category;
    }

    public function create(CategoryWriteDto $dto): Category
    {
        $this->assertNameAvailable($dto->name);
        $category = $this->persist(new Category(['name' => $dto->name]));
        $this->logger->info('category.created', ['category_id' => (int) $category->id]);

        return $category;
    }

    public function update(Category $category, CategoryWriteDto $dto): Category
    {
        $this->assertNameAvailable($dto->name, (int) $category->id);
        $category->name = $dto->name;
        $category = $this->persist($category, (int) $category->id);
        $this->logger->info('category.updated', ['category_id' => (int) $category->id]);

        return $category;
    }

    public function delete(Category $category): bool
    {
        try {
            $this->categories->delete($category);
        } catch (PersistenceException $exception) {
            if ($this->causedByIntegrityViolation($exception)) {
                return false;
            }

            throw $exception;
        }

        $this->logger->info('category.deleted', ['category_id' => (int) $category->id]);

        return true;
    }

    private function assertNameAvailable(string $name, ?int $currentId = null): void
    {
        if ($this->isNameTaken($name, $currentId)) {
            $this->throwNameConflict($name);
        }
    }

    private function persist(Category $category, ?int $currentId = null): Category
    {
        try {
            return $this->categories->save($category);
        } catch (PersistenceException $exception) {
            if ($this->isNameTaken($category->name, $currentId)) {
                $this->throwNameConflict($category->name, $exception);
            }

            throw $exception;
        }
    }

    private function isNameTaken(string $name, ?int $currentId): bool
    {
        $existing = $this->categories->findByName($name);

        return $existing instanceof Category && (int) $existing->id !== $currentId;
    }

    private function throwNameConflict(
        string $name,
        ?PersistenceException $previous = null,
    ): never {
        $this->logger->warning('category.name_conflict', [
            'name_hash' => hash('sha256', $name),
        ]);

        throw new ConflictException('A category with this name already exists.', 0, $previous);
    }

    private function causedByIntegrityViolation(Throwable $exception): bool
    {
        do {
            if ($exception instanceof IntegrityException) {
                return true;
            }

            $exception = $exception->getPrevious();
        } while ($exception instanceof Throwable);

        return false;
    }
}

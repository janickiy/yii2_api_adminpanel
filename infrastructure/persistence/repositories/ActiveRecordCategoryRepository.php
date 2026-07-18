<?php

declare(strict_types=1);

namespace infrastructure\persistence\repositories;

use domain\entities\Category;
use domain\exceptions\PersistenceException;
use domain\mappers\CategoryDataMapperInterface;
use domain\repositories\CategoryRepositoryInterface;
use infrastructure\persistence\records\CategoryRecord;
use Throwable;

/**
 * @phpstan-type CategoryMapper CategoryDataMapperInterface
 */
final readonly class ActiveRecordCategoryRepository implements CategoryRepositoryInterface
{
    /**
     * @param CategoryDataMapperInterface $mapper
     */
    public function __construct(
        private CategoryDataMapperInterface $mapper,
    ) {
    }

    public function findById(int $id): ?Category
    {
        try {
            $data = CategoryRecord::find()
                ->where(['id' => $id])
                ->asArray()
                ->one();

            return is_array($data) ? $this->map($data) : null;
        } catch (Throwable $exception) {
            throw $this->failure('Unable to find the category.', $exception);
        }
    }

    public function findAll(): array
    {
        try {
            $rows = CategoryRecord::find()
                ->orderBy(['name' => SORT_ASC, 'id' => SORT_ASC])
                ->asArray()
                ->all();

            return array_map(fn (array $row): Category => $this->map($row), $rows);
        } catch (Throwable $exception) {
            throw $this->failure('Unable to list categories.', $exception);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function map(array $data): Category
    {
        $entity = $this->mapper->fromArray($data);

        if (!$entity instanceof Category) {
            throw new PersistenceException('The category mapper returned an unexpected entity type.');
        }

        return $entity;
    }

    private function failure(string $message, Throwable $exception): PersistenceException
    {
        return $exception instanceof PersistenceException
            ? $exception
            : new PersistenceException($message, 0, $exception);
    }
}

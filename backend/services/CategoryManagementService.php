<?php

declare(strict_types=1);

namespace backend\services;

use backend\forms\CategoryForm;
use domain\exceptions\PersistenceException;
use domain\services\EventLoggerInterface;
use infrastructure\persistence\records\CategoryRecord;
use yii\db\IntegrityException;

final readonly class CategoryManagementService
{
    public function __construct(
        private EventLoggerInterface $logger,
        private RecordDeleter $recordDeleter,
    ) {
    }

    public function create(CategoryForm $form): bool
    {
        $category = new CategoryRecord(['name' => (string) $form->name]);

        if (!$category->save()) {
            $form->copyErrorsFrom($category);

            return false;
        }

        $this->logger->info('category.created', ['category_id' => (int) $category->id]);

        return true;
    }

    public function update(CategoryRecord $category, CategoryForm $form): bool
    {
        $category->name = (string) $form->name;

        if (!$category->save()) {
            $form->copyErrorsFrom($category);

            return false;
        }

        $this->logger->info('category.updated', ['category_id' => (int) $category->id]);

        return true;
    }

    public function delete(CategoryRecord $category): bool
    {
        try {
            $this->recordDeleter->delete($category);
        } catch (PersistenceException $exception) {
            if ($exception->getPrevious() instanceof IntegrityException) {
                return false;
            }

            throw $exception;
        }

        $this->logger->info('category.deleted', ['category_id' => (int) $category->id]);

        return true;
    }
}

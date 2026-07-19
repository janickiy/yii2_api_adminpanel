<?php

declare(strict_types=1);

namespace infrastructure\persistence\mappers;

use domain\entities\Category;
use domain\mappers\CategoryDataMapperInterface;
use InvalidArgumentException;

final class CategoryDataMapper implements CategoryDataMapperInterface
{
    public function fromArray(array $data): Category
    {
        return new Category(
            (int) ($data['id'] ?? 0),
            (string) ($data['name'] ?? ''),
        );
    }

    public function toArray(object $entity): array
    {
        if (!$entity instanceof Category) {
            throw new InvalidArgumentException(sprintf(
                'Expected %s, got %s.',
                Category::class,
                get_debug_type($entity),
            ));
        }

        return [
            'id' => $entity->id,
            'name' => $entity->name,
        ];
    }
}

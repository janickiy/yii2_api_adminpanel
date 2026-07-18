<?php

declare(strict_types=1);

namespace domain\mappers;

/**
 * @template TEntity of object
 */
interface DataMapperInterface
{
    /**
     * @param array<string, mixed> $data
     * @return TEntity
     */
    public function fromArray(array $data): object;

    /**
     * @param TEntity $entity
     * @return array<string, mixed>
     */
    public function toArray(object $entity): array;
}

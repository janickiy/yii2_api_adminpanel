<?php

declare(strict_types=1);

namespace infrastructure\persistence\mappers;

use domain\entities\Note;
use domain\mappers\NoteDataMapperInterface;
use InvalidArgumentException;

final class NoteDataMapper implements NoteDataMapperInterface
{
    public function fromArray(array $data): Note
    {
        return new Note(
            isset($data['id']) ? (int) $data['id'] : null,
            (int) ($data['user_id'] ?? $data['userId'] ?? 0),
            (int) ($data['category_id'] ?? $data['categoryId'] ?? 0),
            (string) ($data['title'] ?? ''),
            (string) ($data['content'] ?? ''),
            TimestampMapper::fromStorage($data['created_at'] ?? $data['createdAt'] ?? null),
            TimestampMapper::fromStorage($data['updated_at'] ?? $data['updatedAt'] ?? null),
        );
    }

    public function toArray(object $entity): array
    {
        if (!$entity instanceof Note) {
            throw new InvalidArgumentException(sprintf(
                'Expected %s, got %s.',
                Note::class,
                get_debug_type($entity),
            ));
        }

        return [
            'id' => $entity->getId(),
            'user_id' => $entity->getUserId(),
            'category_id' => $entity->getCategoryId(),
            'title' => $entity->getTitle(),
            'content' => $entity->getContent(),
            'created_at' => TimestampMapper::toStorage($entity->getCreatedAt()),
            'updated_at' => TimestampMapper::toStorage($entity->getUpdatedAt()),
        ];
    }
}

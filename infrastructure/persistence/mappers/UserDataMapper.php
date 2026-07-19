<?php

declare(strict_types=1);

namespace infrastructure\persistence\mappers;

use domain\entities\User;
use domain\mappers\UserDataMapperInterface;
use InvalidArgumentException;

final class UserDataMapper implements UserDataMapperInterface
{
    public function fromArray(array $data): User
    {
        return new User(
            isset($data['id']) ? (int) $data['id'] : null,
            (string) ($data['name'] ?? ''),
            (string) ($data['email'] ?? ''),
            (string) ($data['password'] ?? $data['passwordHash'] ?? ''),
            TimestampMapper::fromStorage($data['created_at'] ?? $data['createdAt'] ?? null),
            TimestampMapper::fromStorage($data['updated_at'] ?? $data['updatedAt'] ?? null),
        );
    }

    public function toArray(object $entity): array
    {
        if (!$entity instanceof User) {
            throw new InvalidArgumentException(sprintf(
                'Expected %s, got %s.',
                User::class,
                get_debug_type($entity),
            ));
        }

        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'email' => $entity->email,
            'password' => $entity->passwordHash,
            'created_at' => TimestampMapper::toStorage($entity->createdAt),
            'updated_at' => TimestampMapper::toStorage($entity->updatedAt),
        ];
    }
}

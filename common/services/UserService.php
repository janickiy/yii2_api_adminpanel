<?php

declare(strict_types=1);

namespace common\services;

use common\dtos\UserWriteDto;
use common\entities\User;
use common\repositories\PersistenceException;
use common\repositories\UserRepositoryInterface;
use common\services\exceptions\ConflictException;
use common\services\exceptions\NotFoundException;
use InvalidArgumentException;
use yii\db\ActiveQuery;

final readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $passwordHasher,
        private EventLoggerInterface $logger,
    ) {
    }

    /** @return ActiveQuery<User> */
    public function query(): ActiveQuery
    {
        return $this->users->query();
    }

    public function count(): int
    {
        return $this->users->count();
    }

    public function get(int $id): User
    {
        $user = $this->users->findById($id);
        if (!$user instanceof User) {
            throw new NotFoundException('User not found.');
        }

        return $user;
    }

    public function create(UserWriteDto $dto): User
    {
        if ($dto->password === null || $dto->password === '') {
            throw new InvalidArgumentException('Password is required.');
        }

        $this->assertEmailAvailable($dto->email);
        $user = $this->persist(new User([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $this->passwordHasher->hash($dto->password),
        ]));
        $this->logger->info('user.created.admin', ['user_id' => (int) $user->id]);

        return $user;
    }

    public function update(User $user, UserWriteDto $dto): User
    {
        $this->assertEmailAvailable($dto->email, (int) $user->id);
        $user->name = $dto->name;
        $user->email = $dto->email;
        if ($dto->password !== null && $dto->password !== '') {
            $user->password = $this->passwordHasher->hash($dto->password);
        }

        $user = $this->persist($user, (int) $user->id);
        $this->logger->info('user.updated.admin', ['user_id' => (int) $user->id]);

        return $user;
    }

    public function delete(User $user): void
    {
        $userId = (int) $user->id;
        $this->users->delete($user);
        $this->logger->info('user.deleted.admin', ['user_id' => $userId]);
    }

    private function assertEmailAvailable(string $email, ?int $currentId = null): void
    {
        if ($this->isEmailTaken($email, $currentId)) {
            $this->throwEmailConflict($email);
        }
    }

    private function persist(User $user, ?int $currentId = null): User
    {
        try {
            return $this->users->save($user);
        } catch (PersistenceException $exception) {
            if ($this->isEmailTaken($user->email, $currentId)) {
                $this->throwEmailConflict($user->email, $exception);
            }

            throw $exception;
        }
    }

    private function isEmailTaken(string $email, ?int $currentId): bool
    {
        $existing = $this->users->findByEmail($email);

        return $existing instanceof User && (int) $existing->id !== $currentId;
    }

    private function throwEmailConflict(
        string $email,
        ?PersistenceException $previous = null,
    ): never {
        $this->logger->warning('user.email_conflict.admin', [
            'email_hash' => hash('sha256', $email),
        ]);

        throw new ConflictException('A user with this email already exists.', 0, $previous);
    }
}

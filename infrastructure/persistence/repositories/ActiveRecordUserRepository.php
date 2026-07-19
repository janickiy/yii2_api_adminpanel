<?php

declare(strict_types=1);

namespace infrastructure\persistence\repositories;

use domain\entities\User;
use domain\exceptions\PersistenceException;
use domain\mappers\UserDataMapperInterface;
use domain\repositories\UserRepositoryInterface;
use infrastructure\persistence\records\UserRecord;
use Throwable;

final readonly class ActiveRecordUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private UserDataMapperInterface $mapper,
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        try {
            $data = UserRecord::find()
                ->where(['email' => $email])
                ->asArray()
                ->one();

            return is_array($data) ? $this->map($data) : null;
        } catch (Throwable $exception) {
            throw $this->failure('Unable to find the user by email.', $exception);
        }
    }

    public function findById(int $id): ?User
    {
        try {
            $data = UserRecord::find()
                ->where(['id' => $id])
                ->asArray()
                ->one();

            return is_array($data) ? $this->map($data) : null;
        } catch (Throwable $exception) {
            throw $this->failure('Unable to find the user by id.', $exception);
        }
    }

    public function save(User $user): User
    {
        try {
            $data = $this->mapper->toArray($user);
            $record = $user->id === null
                ? new UserRecord()
                : UserRecord::findOne(['id' => $user->id]);

            if (!$record instanceof UserRecord) {
                throw new PersistenceException('Cannot update a user that does not exist.');
            }

            $record->setAttributes([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'password' => $data['password'] ?? null,
            ], false);

            if (!$record->save()) {
                throw new PersistenceException($this->validationFailure('Unable to save the user.', $record));
            }

            if (!$record->refresh()) {
                throw new PersistenceException('The user was saved but could not be reloaded.');
            }

            return $this->map($record->getAttributes());
        } catch (PersistenceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->failure('Unable to save the user.', $exception);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function map(array $data): User
    {
        return $this->mapper->fromArray($data);
    }

    private function failure(string $message, Throwable $exception): PersistenceException
    {
        return $exception instanceof PersistenceException
            ? $exception
            : new PersistenceException($message, 0, $exception);
    }

    private function validationFailure(string $message, UserRecord $record): string
    {
        $attributes = array_keys($record->getErrors());

        return $attributes === []
            ? $message
            : sprintf('%s Invalid attributes: %s.', $message, implode(', ', $attributes));
    }
}

<?php

declare(strict_types=1);

namespace backend\services;

use backend\forms\UserForm;
use domain\services\EventLoggerInterface;
use domain\services\PasswordHasherInterface;
use infrastructure\persistence\records\UserRecord;

final readonly class UserManagementService
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
        private EventLoggerInterface $logger,
        private RecordDeleter $recordDeleter,
    ) {
    }

    public function create(UserForm $form): bool
    {
        $user = new UserRecord([
            'name' => (string) $form->name,
            'email' => (string) $form->email,
            'password' => $this->passwordHasher->hash((string) $form->password),
        ]);

        if (!$user->save()) {
            $form->copyErrorsFrom($user);

            return false;
        }

        $this->logger->info('user.created.admin', ['user_id' => (int) $user->id]);

        return true;
    }

    public function update(UserRecord $user, UserForm $form): bool
    {
        $user->name = (string) $form->name;
        $user->email = (string) $form->email;

        if ($form->password !== null && $form->password !== '') {
            $user->password = $this->passwordHasher->hash($form->password);
        }

        if (!$user->save()) {
            $form->copyErrorsFrom($user);

            return false;
        }

        $this->logger->info('user.updated.admin', ['user_id' => (int) $user->id]);

        return true;
    }

    public function delete(UserRecord $user): void
    {
        $this->recordDeleter->delete($user);

        $this->logger->info('user.deleted.admin', ['user_id' => (int) $user->id]);
    }
}

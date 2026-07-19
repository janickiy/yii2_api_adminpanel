<?php

declare(strict_types=1);

namespace backend\services;

use common\models\Admin;
use backend\forms\AdminForm;
use domain\services\EventLoggerInterface;

final readonly class AdminManagementService
{
    public function __construct(
        private EventLoggerInterface $logger,
        private RecordDeleter $recordDeleter,
    ) {
    }

    public function create(AdminForm $form): bool
    {
        $admin = new Admin([
            'login' => (string) $form->login,
            'name' => (string) $form->name,
            'role' => (string) $form->role,
        ]);
        $admin->setPassword((string) $form->password);

        if (!$admin->save()) {
            $form->copyErrorsFrom($admin);

            return false;
        }

        $this->logger->info('admin.created', ['admin_id' => (int) $admin->id]);

        return true;
    }

    public function update(Admin $admin, AdminForm $form, int $currentAdminId): bool
    {
        $admin->login = (string) $form->login;
        $admin->name = (string) $form->name;

        if ((int) $admin->id !== $currentAdminId && $form->role !== null) {
            $admin->role = $form->role;
        }

        if ($form->password !== null && $form->password !== '') {
            $admin->setPassword($form->password);
        }

        if (!$admin->save()) {
            $form->copyErrorsFrom($admin);

            return false;
        }

        $this->logger->info('admin.updated', ['admin_id' => (int) $admin->id]);

        return true;
    }

    public function delete(Admin $admin, int $currentAdminId): bool
    {
        if ((int) $admin->id === $currentAdminId) {
            return false;
        }

        $this->recordDeleter->delete($admin);

        $this->logger->info('admin.deleted', ['admin_id' => (int) $admin->id]);

        return true;
    }
}

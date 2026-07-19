<?php

declare(strict_types=1);

namespace tests\Unit;

use backend\services\AdminManagementService;
use backend\services\MessageManagementService;
use backend\services\RecordDeleter;
use common\models\Admin;
use common\models\Message;
use backend\forms\AdminForm;
use backend\forms\UserForm;
use domain\services\EventLoggerInterface;
use infrastructure\persistence\records\UserRecord;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BackofficeServicesTest extends TestCase
{
    public function testFormCopiesRecordErrorsWithoutLeakingUnknownAttributes(): void
    {
        $record = new UserRecord();
        $record->addError('email', 'Email is already used.');
        $record->addError('created_at', 'Unexpected persistence error.');
        $form = new UserForm();

        $form->copyErrorsFrom($record);

        self::assertSame(['Email is already used.'], $form->getErrors('email'));
        self::assertSame(['Unexpected persistence error.'], $form->getErrors(''));
    }

    public function testUpdateScenariosAreRegistered(): void
    {
        $adminForm = new AdminForm(['scenario' => AdminForm::SCENARIO_UPDATE]);
        $userForm = new UserForm(['scenario' => UserForm::SCENARIO_UPDATE]);

        self::assertArrayHasKey(AdminForm::SCENARIO_UPDATE, $adminForm->scenarios());
        self::assertArrayHasKey(UserForm::SCENARIO_UPDATE, $userForm->scenarios());
    }

    public function testAdminServiceRejectsDeletingCurrentIdentityBeforePersistence(): void
    {
        $admin = new class extends Admin {
            public function attributes(): array
            {
                return ['id', 'login', 'password', 'name', 'role', 'auth_key'];
            }
        };
        $admin->setAttribute('id', 42);
        $service = new AdminManagementService($this->logger(), new RecordDeleter());

        self::assertFalse($service->delete($admin, 42));
    }

    public function testMessageServiceRejectsUnknownStatusBeforePersistence(): void
    {
        $message = new Message();
        $service = new MessageManagementService($this->logger(), new RecordDeleter());

        $this->expectException(InvalidArgumentException::class);

        $service->changeStatus($message, 'invalid-status');
    }

    private function logger(): EventLoggerInterface
    {
        return new class implements EventLoggerInterface {
            public function info(string $message, array $context = []): void
            {
            }

            public function warning(string $message, array $context = []): void
            {
            }
        };
    }
}

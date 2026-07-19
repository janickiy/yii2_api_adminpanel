<?php

declare(strict_types=1);

namespace tests\Unit;

use common\dtos\AdminWriteDto;
use common\dtos\CategoryWriteDto;
use common\dtos\UserWriteDto;
use common\entities\Admin;
use common\entities\Category;
use common\entities\Message;
use common\entities\User;
use common\repositories\AdminRepositoryInterface;
use common\repositories\CategoryRepositoryInterface;
use common\repositories\MessageRepositoryInterface;
use common\repositories\PersistenceException;
use common\repositories\UserRepositoryInterface;
use common\services\AdminService;
use common\services\CategoryService;
use common\services\EventLoggerInterface;
use common\services\MessageService;
use common\services\PasswordHasherInterface;
use common\services\UserService;
use common\services\exceptions\ConflictException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use yii\db\BaseActiveRecord;

final class BackofficeServicesTest extends TestCase
{
    public function testAdminServiceRejectsDeletingCurrentIdentityBeforePersistence(): void
    {
        $repository = $this->createMock(AdminRepositoryInterface::class);
        $repository->expects(self::never())->method('delete');
        $service = new AdminService($repository, $this->passwordHasher(), $this->logger());

        self::assertFalse($service->delete($this->admin(42, Admin::ROLE_ADMIN), 42));
    }

    public function testAdminAccessRulesAreHandledByService(): void
    {
        $service = new AdminService(
            $this->createStub(AdminRepositoryInterface::class),
            $this->passwordHasher(),
            $this->logger(),
        );

        self::assertTrue($service->canAccess($this->admin(1, Admin::ROLE_ADMIN), 'moderator'));
        self::assertTrue($service->canAccess($this->admin(2, Admin::ROLE_MODERATOR), 'admin|moderator'));
        self::assertFalse($service->canAccess($this->admin(2, Admin::ROLE_MODERATOR), 'admin'));
    }

    public function testAdminPasswordChangeRotatesRememberMeKey(): void
    {
        $repository = $this->createMock(AdminRepositoryInterface::class);
        $repository->method('findByLogin')->willReturn(null);
        $repository->expects(self::once())
            ->method('save')
            ->willReturnCallback(static fn (Admin $admin): Admin => $admin);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects(self::once())
            ->method('hash')
            ->with('NewSecret!')
            ->willReturn('hashed:new');
        $service = new AdminService($repository, $hasher, $this->logger());
        $admin = $this->admin(2, Admin::ROLE_MODERATOR);
        $oldAuthKey = $admin->auth_key;

        $updated = $service->update(
            $admin,
            new AdminWriteDto(
                name: 'Updated Administrator',
                login: 'admin-2',
                role: Admin::ROLE_MODERATOR,
                password: 'NewSecret!',
            ),
            1,
        );

        self::assertSame('hashed:new', $updated->password);
        self::assertNotSame($oldAuthKey, $updated->auth_key);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $updated->auth_key);
    }

    public function testMessageServiceRejectsUnknownStatusBeforePersistence(): void
    {
        $repository = $this->createMock(MessageRepositoryInterface::class);
        $repository->expects(self::never())->method('save');
        $service = new MessageService($repository, $this->logger());

        $this->expectException(InvalidArgumentException::class);

        $service->changeStatus($this->message(5, Message::STATUS_NEW), 'invalid-status');
    }

    public function testOpeningNewMessagePersistsReadStatusAndLogsEvents(): void
    {
        $repository = $this->createMock(MessageRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('save')
            ->willReturnCallback(static fn (Message $message): Message => $message);
        $events = [];
        $logger = $this->createMock(EventLoggerInterface::class);
        $logger->expects(self::exactly(2))
            ->method('info')
            ->willReturnCallback(static function (string $event) use (&$events): void {
                $events[] = $event;
            });
        $service = new MessageService($repository, $logger);

        $message = $service->markAsRead($this->message(5, Message::STATUS_NEW));

        self::assertSame(Message::STATUS_READ, $message->status);
        self::assertSame(['message.status_changed', 'message.read'], $events);
    }

    public function testUserUpdateHashesOptionalPasswordAndPersistsEntity(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn(null);
        $repository->expects(self::once())
            ->method('save')
            ->willReturnCallback(static fn (User $user): User => $user);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects(self::once())
            ->method('hash')
            ->with('NewSecret!')
            ->willReturn('hashed:NewSecret!');
        $service = new UserService($repository, $hasher, $this->logger());
        $user = $this->user(7, 'Old name', 'old@example.test', 'hashed:old');

        $updated = $service->update(
            $user,
            new UserWriteDto('New name', 'new@example.test', 'NewSecret!'),
        );

        self::assertSame('New name', $updated->name);
        self::assertSame('new@example.test', $updated->email);
        self::assertSame('hashed:NewSecret!', $updated->password);
    }

    public function testUserCreateRejectsMissingPasswordAtServiceBoundary(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::never())->method('findByEmail');
        $repository->expects(self::never())->method('save');
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hash');
        $service = new UserService($repository, $hasher, $this->logger());

        $this->expectException(InvalidArgumentException::class);

        $service->create(new UserWriteDto('User', 'user@example.test', null));
    }

    public function testCategoryUpdateMapsConcurrentUniqueWriteToConflict(): void
    {
        $existing = $this->category(9, 'Work');
        $lookup = 0;
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->method('findByName')
            ->willReturnCallback(static function () use (&$lookup, $existing): ?Category {
                ++$lookup;

                return $lookup === 1 ? null : $existing;
            });
        $persistence = new PersistenceException('Concurrent unique violation.');
        $repository->expects(self::once())->method('save')->willThrowException($persistence);
        $service = new CategoryService($repository, $this->logger());

        try {
            $service->update($this->category(7, 'Personal'), new CategoryWriteDto('Work'));
            self::fail('A concurrent unique write must be exposed as a conflict.');
        } catch (ConflictException $exception) {
            self::assertSame($persistence, $exception->getPrevious());
            self::assertSame(2, $lookup);
        }
    }

    private function admin(int $id, string $role): Admin
    {
        $admin = new Admin();
        $this->hydrate($admin, [
            'id' => $id,
            'login' => 'admin-' . $id,
            'name' => 'Administrator',
            'role' => $role,
            'password' => 'hashed-password',
            'auth_key' => 'auth-key',
        ]);

        return $admin;
    }

    private function message(int $id, string $status): Message
    {
        $message = new Message();
        $this->hydrate($message, [
            'id' => $id,
            'subject' => 'Subject',
            'email' => 'author@example.test',
            'phone' => null,
            'message' => 'Message body',
            'status' => $status,
        ]);

        return $message;
    }

    private function category(int $id, string $name): Category
    {
        $category = new Category();
        $this->hydrate($category, [
            'id' => $id,
            'name' => $name,
        ]);

        return $category;
    }

    private function user(int $id, string $name, string $email, string $password): User
    {
        $user = new User();
        $this->hydrate($user, [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        return $user;
    }

    /** @param array<string, mixed> $attributes */
    private function hydrate(BaseActiveRecord $record, array $attributes): void
    {
        $current = new ReflectionProperty(BaseActiveRecord::class, '_attributes');
        $current->setValue($record, $attributes);
        $old = new ReflectionProperty(BaseActiveRecord::class, '_oldAttributes');
        $old->setValue($record, $attributes);
    }

    private function logger(): EventLoggerInterface
    {
        return $this->createStub(EventLoggerInterface::class);
    }

    private function passwordHasher(): PasswordHasherInterface
    {
        return $this->createStub(PasswordHasherInterface::class);
    }
}

<?php

declare(strict_types=1);

namespace common\services;

use common\dtos\AdminLoginDto;
use common\dtos\AdminWriteDto;
use common\entities\Admin;
use common\repositories\AdminRepositoryInterface;
use common\repositories\PersistenceException;
use common\services\exceptions\AuthenticationException;
use common\services\exceptions\ConflictException;
use common\services\exceptions\NotFoundException;
use InvalidArgumentException;
use yii\db\ActiveQuery;

final readonly class AdminService
{
    public function __construct(
        private AdminRepositoryInterface $admins,
        private PasswordHasherInterface $passwordHasher,
        private EventLoggerInterface $logger,
    ) {
    }

    /** @return ActiveQuery<Admin> */
    public function query(): ActiveQuery
    {
        return $this->admins->query();
    }

    public function count(): int
    {
        return $this->admins->count();
    }

    public function get(int $id): Admin
    {
        $admin = $this->admins->findById($id);
        if (!$admin instanceof Admin) {
            throw new NotFoundException('Administrator not found.');
        }

        return $admin;
    }

    public function authenticate(AdminLoginDto $dto): Admin
    {
        $admin = $this->admins->findByLogin($dto->login);
        if (!$admin instanceof Admin || !$this->passwordHasher->verify($dto->password, $admin->password)) {
            $this->logger->warning('admin.login_failed', [
                'login_hash' => hash('sha256', $dto->login),
            ]);

            throw new AuthenticationException('Invalid login or password.');
        }

        $this->logger->info('admin.login', ['admin_id' => (int) $admin->id]);

        return $admin;
    }

    public function create(AdminWriteDto $dto): Admin
    {
        if ($dto->password === null || $dto->password === '') {
            throw new InvalidArgumentException('Password is required.');
        }

        $this->assertRole($dto->role);
        $this->assertLoginAvailable($dto->login);
        $admin = $this->persist(new Admin([
            'login' => $dto->login,
            'name' => $dto->name,
            'role' => $dto->role,
            'password' => $this->passwordHasher->hash($dto->password),
            'auth_key' => bin2hex(random_bytes(32)),
        ]));
        $this->logger->info('admin.created', ['admin_id' => (int) $admin->id]);

        return $admin;
    }

    public function update(Admin $admin, AdminWriteDto $dto, int $currentAdminId): Admin
    {
        $this->assertRole($dto->role);
        $this->assertLoginAvailable($dto->login, (int) $admin->id);
        $admin->login = $dto->login;
        $admin->name = $dto->name;
        if ((int) $admin->id !== $currentAdminId) {
            $admin->role = $dto->role;
        }
        if ($dto->password !== null && $dto->password !== '') {
            $admin->password = $this->passwordHasher->hash($dto->password);
            $admin->auth_key = bin2hex(random_bytes(32));
        } elseif (trim((string) $admin->auth_key) === '') {
            $admin->auth_key = bin2hex(random_bytes(32));
        }

        $admin = $this->persist($admin, (int) $admin->id);
        $this->logger->info('admin.updated', ['admin_id' => (int) $admin->id]);

        return $admin;
    }

    public function delete(Admin $admin, int $currentAdminId): bool
    {
        if ((int) $admin->id === $currentAdminId) {
            return false;
        }

        $adminId = (int) $admin->id;
        $this->admins->delete($admin);
        $this->logger->info('admin.deleted', ['admin_id' => $adminId]);

        return true;
    }

    public function canAccess(Admin $admin, string $permissions): bool
    {
        return $admin->role === Admin::ROLE_ADMIN
            || in_array($admin->role, explode('|', $permissions), true);
    }

    public function logLogout(int $adminId): void
    {
        $this->logger->info('admin.logout', ['admin_id' => $adminId]);
    }

    private function assertLoginAvailable(string $login, ?int $currentId = null): void
    {
        if ($this->isLoginTaken($login, $currentId)) {
            $this->throwLoginConflict($login);
        }
    }

    private function persist(Admin $admin, ?int $currentId = null): Admin
    {
        try {
            return $this->admins->save($admin);
        } catch (PersistenceException $exception) {
            if ($this->isLoginTaken($admin->login, $currentId)) {
                $this->throwLoginConflict($admin->login, $exception);
            }

            throw $exception;
        }
    }

    private function isLoginTaken(string $login, ?int $currentId): bool
    {
        $existing = $this->admins->findByLogin($login);

        return $existing instanceof Admin && (int) $existing->id !== $currentId;
    }

    private function throwLoginConflict(
        string $login,
        ?PersistenceException $previous = null,
    ): never {
        $this->logger->warning('admin.login_conflict', [
            'login_hash' => hash('sha256', $login),
        ]);

        throw new ConflictException('An administrator with this login already exists.', 0, $previous);
    }

    private function assertRole(string $role): void
    {
        if (!in_array($role, [Admin::ROLE_ADMIN, Admin::ROLE_MODERATOR], true)) {
            throw new InvalidArgumentException('Unknown administrator role.');
        }
    }
}

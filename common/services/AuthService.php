<?php

declare(strict_types=1);

namespace common\services;

use common\dtos\AuthenticationResultDto;
use common\dtos\LoginUserDto;
use common\dtos\RegisterUserDto;
use common\entities\User;
use common\repositories\PersistenceException;
use common\repositories\UserRepositoryInterface;
use common\services\exceptions\AuthenticationException;
use common\services\exceptions\ConflictException;

final readonly class AuthService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $passwordHasher,
        private TokenManagerInterface $tokens,
        private EventLoggerInterface $logger,
    ) {
    }

    public function register(RegisterUserDto $dto): User
    {
        if ($this->users->findByEmail($dto->email) !== null) {
            $this->throwRegistrationConflict($dto->email);
        }

        $user = new User([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $this->passwordHasher->hash($dto->password),
        ]);

        try {
            $user = $this->users->save($user);
        } catch (PersistenceException $exception) {
            if ($this->users->findByEmail($dto->email) !== null) {
                $this->throwRegistrationConflict($dto->email, $exception);
            }

            $this->logger->warning('auth.registration_failed', [
                'email_hash' => hash('sha256', $dto->email),
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }

        $this->logger->info('auth.user_registered', ['user_id' => (int) $user->id]);

        return $user;
    }

    public function login(LoginUserDto $dto): AuthenticationResultDto
    {
        $user = $this->users->findByEmail($dto->email);

        if (!$user instanceof User || !$this->passwordHasher->verify($dto->password, $user->password)) {
            $this->logger->warning('auth.login_failed', [
                'email_hash' => hash('sha256', $dto->email),
            ]);

            throw new AuthenticationException('Invalid email or password.');
        }

        $token = $this->tokens->issue($user);
        $this->logger->info('auth.login_succeeded', ['user_id' => (int) $user->id]);

        return new AuthenticationResultDto($user, $token);
    }

    public function logout(string $token): void
    {
        try {
            $userId = $this->tokens->validateAndGetUserId($token);
        } catch (AuthenticationException $exception) {
            $this->logger->warning('auth.logout_failed', ['reason' => $exception->getMessage()]);

            throw $exception;
        }

        $this->tokens->revoke($token);
        $this->logger->info('auth.logout_succeeded', ['user_id' => $userId]);
    }

    private function throwRegistrationConflict(
        string $email,
        ?PersistenceException $previous = null,
    ): never {
        $this->logger->warning('auth.registration_conflict', [
            'email_hash' => hash('sha256', $email),
        ]);

        throw new ConflictException('A user with this email already exists.', 0, $previous);
    }
}

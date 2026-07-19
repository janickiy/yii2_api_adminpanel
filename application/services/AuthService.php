<?php

declare(strict_types=1);

namespace application\services;

use application\dto\auth\LoginUserDto;
use application\dto\auth\RegisterUserDto;
use application\results\AuthenticationResult;
use domain\entities\User;
use domain\exceptions\AuthenticationException;
use domain\exceptions\ConflictException;
use domain\exceptions\PersistenceException;
use domain\repositories\UserRepositoryInterface;
use domain\services\EventLoggerInterface;
use domain\services\PasswordHasherInterface;
use domain\services\TokenManagerInterface;

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
        $email = $dto->email;

        if ($this->users->findByEmail($email) !== null) {
            $this->throwRegistrationConflict($email);
        }

        $user = new User(
            id: null,
            name: $dto->name,
            email: $email,
            passwordHash: $this->passwordHasher->hash($dto->password),
        );

        try {
            $savedUser = $this->users->save($user);
        } catch (PersistenceException $exception) {
            if ($this->users->findByEmail($email) !== null) {
                $this->throwRegistrationConflict($email, $exception);
            }

            $this->logger->warning('auth.registration_failed', [
                'email_hash' => hash('sha256', $email),
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }

        $this->logger->info('auth.user_registered', [
            'user_id' => $savedUser->id,
        ]);

        return $savedUser;
    }

    public function login(LoginUserDto $dto): AuthenticationResult
    {
        $email = $dto->email;
        $user = $this->users->findByEmail($email);

        if ($user === null || !$this->passwordHasher->verify($dto->password, $user->passwordHash)) {
            $this->logger->warning('auth.login_failed', [
                'email_hash' => hash('sha256', $email),
            ]);

            throw new AuthenticationException('Invalid email or password.');
        }

        $token = $this->tokens->issue($user);

        $this->logger->info('auth.login_succeeded', [
            'user_id' => $user->id,
        ]);

        return new AuthenticationResult($user, $token);
    }

    public function logout(string $token): void
    {
        try {
            $userId = $this->tokens->validateAndGetUserId($token);
        } catch (AuthenticationException $exception) {
            $this->logger->warning('auth.logout_failed', [
                'reason' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->tokens->revoke($token);

        $this->logger->info('auth.logout_succeeded', [
            'user_id' => $userId,
        ]);
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

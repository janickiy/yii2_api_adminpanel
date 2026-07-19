<?php

declare(strict_types=1);

namespace tests\Unit;

use DateTimeImmutable;
use application\dto\auth\LoginUserDto;
use application\dto\auth\RegisterUserDto;
use application\services\AuthService;
use domain\entities\User;
use domain\exceptions\AuthenticationException;
use domain\exceptions\ConflictException;
use domain\repositories\UserRepositoryInterface;
use domain\services\EventLoggerInterface;
use domain\services\PasswordHasherInterface;
use domain\services\TokenManagerInterface;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    public function testRegisterCreatesUserFromDto(): void
    {
        $users = new AuthTestUserRepository();
        $logger = new AuthTestLogger();
        $service = $this->service($users, logger: $logger);
        $dto = $this->registerDto('Alice Example', 'alice@example.test');

        $user = $service->register($dto);

        self::assertSame(1, $user->id);
        self::assertSame('Alice Example', $user->name);
        self::assertSame('alice@example.test', $user->email);
        self::assertSame('hashed:Secret123!', $user->passwordHash);
        self::assertSame($user, $users->findByEmail('alice@example.test'));
        self::assertSame('auth.user_registered', $logger->infoEvents[0]['message'] ?? null);
    }

    public function testCommandsCanonicalizeIdentityDataOutsideHttp(): void
    {
        $users = new AuthTestUserRepository();
        $service = $this->service($users);

        $user = $service->register($this->registerDto('  Alice Example  ', '  ALICE@EXAMPLE.TEST  '));
        $result = $service->login($this->loginDto(' ALICE@EXAMPLE.TEST ', 'Secret123!'));

        self::assertSame('Alice Example', $user->name);
        self::assertSame('alice@example.test', $user->email);
        self::assertSame($user->id, $result->user->id);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $users = new AuthTestUserRepository();
        $service = $this->service($users);
        $service->register($this->registerDto('Alice', 'alice@example.test'));

        $this->expectException(ConflictException::class);

        $service->register($this->registerDto('Another Alice', 'alice@example.test'));
    }

    public function testLoginReturnsTokenForValidCredentials(): void
    {
        $users = new AuthTestUserRepository([
            new User(7, 'Alice', 'alice@example.test', 'hashed:Secret123!'),
        ]);
        $tokens = new AuthTestTokenManager();
        $service = $this->service($users, tokens: $tokens);
        $dto = $this->loginDto('alice@example.test', 'Secret123!');

        $result = $service->login($dto);

        self::assertSame(7, $result->user->id);
        self::assertSame('token-7-1', $result->token);
        self::assertSame(7, $tokens->validateAndGetUserId($result->token));
    }

    public function testLoginRejectsInvalidCredentials(): void
    {
        $users = new AuthTestUserRepository([
            new User(7, 'Alice', 'alice@example.test', 'hashed:Secret123!'),
        ]);
        $service = $this->service($users);
        $dto = $this->loginDto('alice@example.test', 'WrongPassword!');

        $this->expectException(AuthenticationException::class);

        $service->login($dto);
    }

    public function testLogoutRevokesIssuedToken(): void
    {
        $user = new User(11, 'Bob', 'bob@example.test', 'hashed:Secret123!');
        $users = new AuthTestUserRepository([$user]);
        $tokens = new AuthTestTokenManager();
        $service = $this->service($users, tokens: $tokens);
        $token = $tokens->issue($user);

        $service->logout($token);

        self::assertTrue($tokens->isRevoked($token));
    }

    private function service(
        AuthTestUserRepository $users,
        ?AuthTestPasswordHasher $hasher = null,
        ?AuthTestTokenManager $tokens = null,
        ?AuthTestLogger $logger = null,
    ): AuthService {
        return new AuthService(
            $users,
            $hasher ?? new AuthTestPasswordHasher(),
            $tokens ?? new AuthTestTokenManager(),
            $logger ?? new AuthTestLogger(),
        );
    }

    private function registerDto(string $name, string $email): RegisterUserDto
    {
        return new RegisterUserDto(
            name: $name,
            email: $email,
            password: 'Secret123!',
        );
    }

    private function loginDto(string $email, string $password): LoginUserDto
    {
        return new LoginUserDto($email, $password);
    }
}

final class AuthTestUserRepository implements UserRepositoryInterface
{
    /** @var array<int, User> */
    private array $users = [];
    private int $nextId = 1;

    /**
     * @param list<User> $users
     */
    public function __construct(array $users = [])
    {
        foreach ($users as $user) {
            $this->save($user);
        }
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
                return $user;
            }
        }

        return null;
    }

    public function findById(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function save(User $user): User
    {
        $id = $user->id ?? $this->nextId++;
        $this->nextId = max($this->nextId, $id + 1);
        $now = new DateTimeImmutable('2026-07-19 12:00:00+00:00');
        $saved = new User(
            $id,
            $user->name,
            $user->email,
            $user->passwordHash,
            $user->createdAt ?? $now,
            $now,
        );
        $this->users[$id] = $saved;

        return $saved;
    }
}

final class AuthTestPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $password): string
    {
        return 'hashed:' . $password;
    }

    public function verify(string $password, string $passwordHash): bool
    {
        return hash_equals('hashed:' . $password, $passwordHash);
    }
}

final class AuthTestTokenManager implements TokenManagerInterface
{
    /** @var array<string, int> */
    private array $tokens = [];
    /** @var array<string, true> */
    private array $revoked = [];

    public function issue(User $user): string
    {
        if ($user->id === null) {
            throw new AuthenticationException('Persisted user required.');
        }

        $token = sprintf('token-%d-%d', $user->id, count($this->tokens) + 1);
        $this->tokens[$token] = $user->id;

        return $token;
    }

    public function validateAndGetUserId(string $token): int
    {
        if (!isset($this->tokens[$token]) || isset($this->revoked[$token])) {
            throw new AuthenticationException('Invalid token.');
        }

        return $this->tokens[$token];
    }

    public function revoke(string $token): void
    {
        $this->validateAndGetUserId($token);
        $this->revoked[$token] = true;
    }

    public function isRevoked(string $token): bool
    {
        return isset($this->revoked[$token]);
    }
}

final class AuthTestLogger implements EventLoggerInterface
{
    /** @var list<array{message: string, context: array<string, mixed>}> */
    public array $infoEvents = [];
    /** @var list<array{message: string, context: array<string, mixed>}> */
    public array $warningEvents = [];

    public function info(string $message, array $context = []): void
    {
        $this->infoEvents[] = ['message' => $message, 'context' => $context];
    }

    public function warning(string $message, array $context = []): void
    {
        $this->warningEvents[] = ['message' => $message, 'context' => $context];
    }
}

<?php

declare(strict_types=1);

namespace tests\Unit;

use common\dtos\LoginUserDto;
use common\dtos\RegisterUserDto;
use common\entities\User;
use common\repositories\PersistenceException;
use common\repositories\UserRepositoryInterface;
use common\services\AuthService;
use common\services\EventLoggerInterface;
use common\services\PasswordHasherInterface;
use common\services\TokenManagerInterface;
use common\services\exceptions\AuthenticationException;
use common\services\exceptions\ConflictException;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;
use yii\db\ActiveQuery;
use yii\db\ColumnSchema;
use yii\db\Connection;
use yii\db\Schema;
use yii\db\TableSchema;
use yii\web\Application as WebApplication;

final class AuthServiceTest extends TestCase
{
    private static Application|WebApplication|null $previousApplication;

    public static function setUpBeforeClass(): void
    {
        self::$previousApplication = Yii::$app;
        Yii::$app = new AuthTestApplication(new AuthTestConnection());
    }

    public static function tearDownAfterClass(): void
    {
        Yii::$app = self::$previousApplication;
    }

    public function testRegisterCreatesAndLogsUserFromDto(): void
    {
        $users = new AuthTestUserRepository();
        $logger = new AuthTestLogger();
        $service = $this->service($users, logger: $logger);

        $user = $service->register(new RegisterUserDto(
            name: 'Alice Example',
            email: 'alice@example.test',
            password: 'Secret123!',
        ));

        self::assertSame(1, (int) $user->id);
        self::assertSame('Alice Example', $user->name);
        self::assertSame('alice@example.test', $user->email);
        self::assertSame('hashed:Secret123!', $user->password);
        self::assertSame($user, $users->findByEmail('alice@example.test'));
        self::assertSame([
            'message' => 'auth.user_registered',
            'context' => ['user_id' => 1],
        ], $logger->infoEvents[0] ?? null);
    }

    public function testRegisterRejectsExistingEmailAndLogsOnlyItsHash(): void
    {
        $users = new AuthTestUserRepository([
            $this->user(7, 'Alice', 'alice@example.test', 'hashed:Secret123!'),
        ]);
        $logger = new AuthTestLogger();
        $service = $this->service($users, logger: $logger);

        try {
            $service->register(new RegisterUserDto('Another Alice', 'alice@example.test', 'Secret123!'));
            self::fail('An existing email must cause a conflict.');
        } catch (ConflictException $exception) {
            self::assertSame('A user with this email already exists.', $exception->getMessage());
        }

        self::assertSame('auth.registration_conflict', $logger->warningEvents[0]['message'] ?? null);
        self::assertSame(
            hash('sha256', 'alice@example.test'),
            $logger->warningEvents[0]['context']['email_hash'] ?? null,
        );
        self::assertStringNotContainsString(
            'alice@example.test',
            json_encode($logger->warningEvents, JSON_THROW_ON_ERROR),
        );
    }

    public function testRegisterMapsConcurrentUniqueViolationToConflict(): void
    {
        $users = new AuthTestUserRepository();
        $users->simulateDuplicateOnNextSave();
        $logger = new AuthTestLogger();
        $service = $this->service($users, logger: $logger);

        $this->expectException(ConflictException::class);

        try {
            $service->register(new RegisterUserDto('Alice', 'alice@example.test', 'Secret123!'));
        } finally {
            self::assertSame('auth.registration_conflict', $logger->warningEvents[0]['message'] ?? null);
        }
    }

    public function testLoginReturnsTokenAndLogsSuccessForValidCredentials(): void
    {
        $users = new AuthTestUserRepository([
            $this->user(7, 'Alice', 'alice@example.test', 'hashed:Secret123!'),
        ]);
        $tokens = new AuthTestTokenManager();
        $logger = new AuthTestLogger();
        $service = $this->service($users, tokens: $tokens, logger: $logger);

        $result = $service->login(new LoginUserDto('alice@example.test', 'Secret123!'));

        self::assertSame(7, (int) $result->user->id);
        self::assertSame('token-7-1', $result->token);
        self::assertSame(7, $tokens->validateAndGetUserId($result->token));
        self::assertSame([
            'message' => 'auth.login_succeeded',
            'context' => ['user_id' => 7],
        ], $logger->infoEvents[0] ?? null);
    }

    public function testLoginRejectsInvalidCredentialsAndLogsFailure(): void
    {
        $users = new AuthTestUserRepository([
            $this->user(7, 'Alice', 'alice@example.test', 'hashed:Secret123!'),
        ]);
        $logger = new AuthTestLogger();
        $service = $this->service($users, logger: $logger);

        try {
            $service->login(new LoginUserDto('alice@example.test', 'WrongPassword!'));
            self::fail('Invalid credentials must be rejected.');
        } catch (AuthenticationException $exception) {
            self::assertSame('Invalid email or password.', $exception->getMessage());
        }

        self::assertSame('auth.login_failed', $logger->warningEvents[0]['message'] ?? null);
        self::assertSame(
            hash('sha256', 'alice@example.test'),
            $logger->warningEvents[0]['context']['email_hash'] ?? null,
        );
    }

    public function testLogoutRevokesIssuedTokenAndLogsUser(): void
    {
        $user = $this->user(11, 'Bob', 'bob@example.test', 'hashed:Secret123!');
        $tokens = new AuthTestTokenManager();
        $logger = new AuthTestLogger();
        $service = $this->service(
            new AuthTestUserRepository([$user]),
            tokens: $tokens,
            logger: $logger,
        );
        $token = $tokens->issue($user);

        $service->logout($token);

        self::assertTrue($tokens->isRevoked($token));
        self::assertSame([
            'message' => 'auth.logout_succeeded',
            'context' => ['user_id' => 11],
        ], $logger->infoEvents[0] ?? null);
    }

    public function testLogoutRejectsInvalidTokenAndLogsFailure(): void
    {
        $logger = new AuthTestLogger();
        $service = $this->service(new AuthTestUserRepository(), logger: $logger);

        $this->expectException(AuthenticationException::class);

        try {
            $service->logout('unknown-token');
        } finally {
            self::assertSame('auth.logout_failed', $logger->warningEvents[0]['message'] ?? null);
        }
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

    private function user(int $id, string $name, string $email, string $password): User
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        $user->setAttributes([
            'id' => $id,
            'created_at' => '2026-07-19 12:00:00+00:00',
            'updated_at' => '2026-07-19 12:00:00+00:00',
        ], false);

        return $user;
    }
}

final class AuthTestUserRepository implements UserRepositoryInterface
{
    /** @var array<int, User> */
    private array $users = [];
    private int $nextId = 1;
    private bool $duplicateOnNextSave = false;

    /** @param list<User> $users */
    public function __construct(array $users = [])
    {
        foreach ($users as $user) {
            $this->store($user);
        }
    }

    public function simulateDuplicateOnNextSave(): void
    {
        $this->duplicateOnNextSave = true;
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
        if ($this->duplicateOnNextSave) {
            $this->duplicateOnNextSave = false;
            $duplicate = new User([
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
            ]);
            $this->store($duplicate);

            throw new PersistenceException('Simulated unique constraint violation.');
        }

        $this->store($user);

        return $user;
    }

    public function query(): ActiveQuery
    {
        /** @var ActiveQuery<User> $query */
        $query = new ActiveQuery(User::class);

        return $query;
    }

    public function count(): int
    {
        return count($this->users);
    }

    public function delete(User $user): void
    {
        unset($this->users[(int) $user->id]);
    }

    private function store(User $user): void
    {
        $id = (int) ($user->id ?: $this->nextId);
        $this->nextId = max($this->nextId, $id + 1);
        $user->setAttributes([
            'id' => $id,
            'created_at' => $user->created_at ?: '2026-07-19 12:00:00+00:00',
            'updated_at' => '2026-07-19 12:00:00+00:00',
        ], false);
        $this->users[$id] = $user;
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
        $userId = (int) $user->id;
        if ($userId < 1) {
            throw new AuthenticationException('Persisted user required.');
        }

        $token = sprintf('token-%d-%d', $userId, count($this->tokens) + 1);
        $this->tokens[$token] = $userId;

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

final class AuthTestApplication extends Application
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getDb(): Connection
    {
        return $this->connection;
    }
}

final class AuthTestConnection extends Connection
{
    private ?AuthTestSchema $testSchema = null;

    public function getSchema(): Schema
    {
        return $this->testSchema ??= new AuthTestSchema(['db' => $this]);
    }
}

final class AuthTestSchema extends Schema
{
    protected function loadTableSchema($name): ?TableSchema
    {
        if ($name !== 'users') {
            return null;
        }

        $columns = [];
        foreach (['id', 'name', 'email', 'password', 'created_at', 'updated_at'] as $columnName) {
            $columns[$columnName] = new ColumnSchema(['name' => $columnName]);
        }

        return new TableSchema([
            'name' => 'users',
            'fullName' => 'users',
            'primaryKey' => ['id'],
            'columns' => $columns,
        ]);
    }
}

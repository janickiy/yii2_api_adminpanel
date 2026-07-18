<?php

declare(strict_types=1);

namespace tests\Api;

use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NotesApiTest extends TestCase
{
    private string $baseUrl;
    /** @var list<string> */
    private array $createdEmails = [];
    /** @var list<string> */
    private array $issuedTokenIds = [];

    protected function setUp(): void
    {
        $this->baseUrl = rtrim((string) getenv('API_TEST_BASE_URL'), '/');
        if ($this->baseUrl === '') {
            if (filter_var(getenv('CI'), FILTER_VALIDATE_BOOL)) {
                self::fail('API_TEST_BASE_URL is required in CI.');
            }

            self::markTestSkipped('Set API_TEST_BASE_URL to run the Docker API test.');
        }
    }

    protected function tearDown(): void
    {
        try {
            $this->cleanCreatedRecords();
        } finally {
            parent::tearDown();
        }
    }

    public function testCompleteJwtNotesLifecycleAndOwnership(): void
    {
        [$status] = $this->request('GET', '/api/v1/notes');
        self::assertSame(401, $status);

        $suffix = bin2hex(random_bytes(6));
        $first = [
            'name' => 'API User',
            'email' => "api-{$suffix}@example.test",
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ];
        [$status, $body] = $this->request('POST', '/api/v1/register', $first);
        if ($status === 201) {
            $this->createdEmails[] = $first['email'];
        }
        self::assertSame(201, $status, json_encode($body));
        self::assertSame($first['email'], $body['data']['email'] ?? null);

        [$status] = $this->request('POST', '/api/v1/register', $first);
        self::assertSame(409, $status);

        [$status, $body] = $this->request('POST', '/api/v1/login', [
            'email' => $first['email'],
            'password' => $first['password'],
        ]);
        self::assertSame(200, $status, json_encode($body));
        $firstToken = (string) ($body['data']['token'] ?? '');
        self::assertNotSame('', $firstToken);
        $this->issuedTokenIds[] = $this->tokenId($firstToken);

        [$status] = $this->request(
            'GET',
            '/api/v1/notes?page=9223372036854775807&per_page=100',
            token: $firstToken,
        );
        self::assertSame(422, $status);

        [$status, $body] = $this->request('GET', '/api/v1/categories', token: $firstToken);
        self::assertSame(200, $status, json_encode($body));
        $categoryId = (int) ($body['data'][0]['id'] ?? 0);
        self::assertGreaterThan(0, $categoryId);

        [$status] = $this->request('POST', '/api/v1/notes', [
            'category_id' => 0,
            'title' => '',
            'content' => '',
        ], $firstToken);
        self::assertSame(422, $status);

        [$status, $body] = $this->request('POST', '/api/v1/notes', [
            'category_id' => $categoryId,
            'title' => 'Первая заметка',
            'content' => 'Проверка полного API-цикла.',
        ], $firstToken);
        self::assertSame(201, $status, json_encode($body));
        $noteId = (int) ($body['data']['id'] ?? 0);
        self::assertGreaterThan(0, $noteId);

        [$status, $body] = $this->request(
            'GET',
            "/api/v1/notes?category_id={$categoryId}&page=1&per_page=10",
            token: $firstToken,
        );
        self::assertSame(200, $status, json_encode($body));
        self::assertSame($noteId, (int) ($body['data'][0]['id'] ?? 0));
        self::assertGreaterThanOrEqual(1, (int) ($body['meta']['total'] ?? 0));

        [$status, $body] = $this->request('PUT', "/api/v1/notes/{$noteId}", [
            'category_id' => $categoryId,
            'title' => 'Обновлённая заметка',
            'content' => 'Кэш должен быть инвалидирован.',
        ], $firstToken);
        self::assertSame(200, $status, json_encode($body));
        self::assertSame('Обновлённая заметка', $body['data']['title'] ?? null);

        [$status, $body] = $this->request(
            'GET',
            "/api/v1/notes?category_id={$categoryId}&page=1&per_page=10",
            token: $firstToken,
        );
        self::assertSame(200, $status, json_encode($body));
        self::assertSame('Обновлённая заметка', $body['data'][0]['title'] ?? null);

        $second = [
            'name' => 'Second User',
            'email' => "api-second-{$suffix}@example.test",
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ];
        [$status] = $this->request('POST', '/api/v1/register', $second);
        if ($status === 201) {
            $this->createdEmails[] = $second['email'];
        }
        self::assertSame(201, $status);
        [$status, $body] = $this->request('POST', '/api/v1/login', [
            'email' => $second['email'],
            'password' => $second['password'],
        ]);
        self::assertSame(200, $status, json_encode($body));
        $secondToken = (string) ($body['data']['token'] ?? '');
        self::assertNotSame('', $secondToken);
        $this->issuedTokenIds[] = $this->tokenId($secondToken);

        [$status] = $this->request('GET', "/api/v1/notes/{$noteId}", token: $secondToken);
        self::assertSame(404, $status);

        [$status] = $this->request('DELETE', "/api/v1/notes/{$noteId}", token: $firstToken);
        self::assertSame(204, $status);

        [$status] = $this->request('GET', "/api/v1/notes/{$noteId}", token: $firstToken);
        self::assertSame(404, $status);

        [$status] = $this->request('POST', '/api/v1/logout', token: $firstToken);
        self::assertSame(204, $status);
        [$status] = $this->request('GET', '/api/v1/categories', token: $firstToken);
        self::assertSame(401, $status);
    }

    private function cleanCreatedRecords(): void
    {
        if ($this->createdEmails === [] && $this->issuedTokenIds === []) {
            return;
        }

        $host = (string) (getenv('DB_HOST') ?: 'postgres');
        $port = (int) (getenv('DB_PORT') ?: 5432);
        $database = (string) (getenv('DB_DATABASE') ?: 'notes');
        $username = (string) (getenv('DB_USERNAME') ?: 'notes');
        $password = (string) (getenv('DB_PASSWORD') ?: 'notes');
        $pdo = new PDO(
            "pgsql:host={$host};port={$port};dbname={$database}",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $pdo->beginTransaction();
        try {
            $this->deleteWhereIn($pdo, 'revoked_tokens', 'jti', $this->issuedTokenIds);
            $this->deleteWhereIn($pdo, 'users', 'email', $this->createdEmails);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param list<string> $values
     */
    private function deleteWhereIn(PDO $pdo, string $table, string $column, array $values): void
    {
        if ($values === []) {
            return;
        }

        $allowedTargets = [
            'revoked_tokens.jti',
            'users.email',
        ];
        if (!in_array($table . '.' . $column, $allowedTargets, true)) {
            throw new RuntimeException('Unexpected API test cleanup target.');
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $statement = $pdo->prepare("DELETE FROM {$table} WHERE {$column} IN ({$placeholders})");
        $statement->execute($values);
    }

    private function tokenId(string $token): string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('The API returned a malformed JWT.');
        }

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode($payload, true);
        $claims = $decoded === false ? null : json_decode($decoded, true);
        $jti = is_array($claims) ? ($claims['jti'] ?? null) : null;

        if (!is_string($jti) || $jti === '') {
            throw new RuntimeException('The API JWT does not contain a valid jti claim.');
        }

        return $jti;
    }

    /** @return array{0:int,1:array<string,mixed>} */
    private function request(
        string $method,
        string $path,
        ?array $payload = null,
        ?string $token = null,
    ): array {
        $headers = ['Accept: application/json'];
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $payload === null ? '' : json_encode($payload, JSON_THROW_ON_ERROR),
                'ignore_errors' => true,
                'timeout' => 10,
            ],
        ]);

        $http_response_header = [];
        $raw = @file_get_contents($this->baseUrl . $path, false, $context);
        $responseHeaders = $http_response_header;
        $statusLine = (string) ($responseHeaders[0] ?? '');
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $status = (int) ($matches[1] ?? 0);

        self::assertNotSame(0, $status, 'API is unavailable: ' . $this->baseUrl . $path);

        return [
            $status,
            $raw === false || $raw === '' ? [] : (array) json_decode($raw, true, 512, JSON_THROW_ON_ERROR),
        ];
    }
}

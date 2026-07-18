<?php

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdvancedStructureTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testLayeredDirectoriesAndAliasesExist(): void
    {
        self::assertDirectoryExists($this->root . '/application');
        self::assertDirectoryExists($this->root . '/domain');
        self::assertDirectoryExists($this->root . '/infrastructure');
        self::assertSame($this->root . '/application', \Yii::getAlias('@application'));
        self::assertSame($this->root . '/domain', \Yii::getAlias('@domain'));
        self::assertSame($this->root . '/infrastructure', \Yii::getAlias('@infrastructure'));
    }

    public function testDockerUsesPhp84AndPostgreSqlOnly(): void
    {
        $dockerfile = (string) file_get_contents($this->root . '/docker/php/Dockerfile');
        $compose = (string) file_get_contents($this->root . '/docker-compose.yml');

        self::assertStringContainsString('FROM php:8.4-fpm-alpine', $dockerfile);
        self::assertStringContainsString('pdo_pgsql', $dockerfile);
        self::assertStringContainsString('postgres:', $compose);
        self::assertStringContainsString('redis:', $compose);
        self::assertStringContainsString('memcached:', $compose);
        self::assertStringNotContainsString('mysql:', $compose);
    }

    public function testTrackedOpenApiContractContainsCanonicalResources(): void
    {
        $spec = (string) file_get_contents(
            $this->root . '/frontend/modules/api/openapi/openapi.yaml',
        );

        self::assertStringContainsString('/api/v1/register:', $spec);
        self::assertStringContainsString('/api/v1/login:', $spec);
        self::assertStringContainsString('/api/v1/logout:', $spec);
        self::assertStringContainsString('/api/v1/categories:', $spec);
        self::assertStringContainsString('/api/v1/notes:', $spec);
        self::assertStringContainsString('/api/v1/notes/{id}:', $spec);
        self::assertStringContainsString('bearerAuth:', $spec);
    }
}

<?php

declare(strict_types=1);

namespace tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class AdvancedStructureTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testCommonApplicationStructureIsCanonical(): void
    {
        foreach (['dtos', 'entities', 'repositories', 'services'] as $directory) {
            self::assertDirectoryExists($this->root . '/common/' . $directory);
        }

        foreach (
            [
                'application',
                'domain',
                'infrastructure',
                'common/models',
                'backend/services',
                'frontend/services',
                'frontend/modules/api',
            ] as $directory
        ) {
            self::assertDirectoryDoesNotExist($this->root . '/' . $directory);
        }
    }

    public function testRemovedMapperImplementationIsAbsentFromProductionCode(): void
    {
        $legacyPattern = 'Data' . 'Mapper';

        foreach (['common', 'backend', 'frontend', 'console'] as $directory) {
            foreach ($this->phpFiles($this->root . '/' . $directory) as $path => $source) {
                self::assertStringNotContainsString(
                    $legacyPattern,
                    $source,
                    sprintf('%s still references the removed mapper abstraction.', $path),
                );
            }
        }
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
        $spec = (string) file_get_contents($this->root . '/frontend/openapi/openapi.yaml');

        self::assertStringContainsString('/api/v1/register:', $spec);
        self::assertStringContainsString('/api/v1/login:', $spec);
        self::assertStringContainsString('/api/v1/logout:', $spec);
        self::assertStringContainsString('/api/v1/categories:', $spec);
        self::assertStringContainsString('/api/v1/notes:', $spec);
        self::assertStringContainsString('/api/v1/notes/{id}:', $spec);
        self::assertStringContainsString('bearerAuth:', $spec);
    }

    public function testBackofficeUsesSweetAlertForDeletionConfirmation(): void
    {
        $composer = (string) file_get_contents($this->root . '/composer.json');
        $layout = (string) file_get_contents($this->root . '/backend/views/layouts/admin.php');
        $confirmation = (string) file_get_contents(
            $this->root . '/backend/web/js/admin-confirmation.js',
        );

        self::assertStringContainsString('npm-asset/sweetalert2', $composer);
        self::assertStringContainsString('AdminAsset::register($this)', $layout);
        self::assertStringContainsString('yii.confirm =', $confirmation);
        self::assertStringContainsString('Swal.fire({', $confirmation);
        self::assertStringNotContainsString('window.confirm(', $layout . $confirmation);
    }

    /** @return iterable<string, string> */
    private function phpFiles(string $directory): iterable
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            if (str_contains($path, '/runtime/') || str_contains($path, '/web/assets/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            yield ltrim(substr($path, strlen($this->root)), DIRECTORY_SEPARATOR) => $source;
        }
    }
}

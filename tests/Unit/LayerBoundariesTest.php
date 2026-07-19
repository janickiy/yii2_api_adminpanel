<?php

declare(strict_types=1);

namespace tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class LayerBoundariesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testCommonServicesAndRepositoriesDoNotDependOnHttpClasses(): void
    {
        $forbidden = [
            'backend\\forms\\',
            'backend\\controllers\\',
            'frontend\\forms\\',
            'frontend\\controllers\\',
        ];

        foreach (['common/services', 'common/repositories'] as $directory) {
            $this->assertFilesDoNotReference($directory, $forbidden);
        }
    }

    public function testRemovedNamespacesAreAbsentFromProjectPhpSources(): void
    {
        $forbidden = [
            'applica' . 'tion\\',
            'do' . 'main\\',
            'infra' . 'structure\\',
            'common\\' . 'models\\',
            'backend\\' . 'services\\',
            'frontend\\' . 'services\\',
            'frontend\\modules\\' . 'api\\',
        ];

        foreach (['common', 'backend', 'frontend', 'console', 'tests'] as $directory) {
            $this->assertFilesDoNotReference($directory, $forbidden);
        }
    }

    /** @param list<string> $forbiddenReferences */
    private function assertFilesDoNotReference(string $directory, array $forbiddenReferences): void
    {
        foreach ($this->phpFiles($this->root . '/' . $directory) as $path => $source) {
            foreach ($forbiddenReferences as $reference) {
                self::assertStringNotContainsString(
                    $reference,
                    $source,
                    sprintf('%s must not depend on %s', $this->relativePath($path), $reference),
                );
            }
        }
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

            yield $path => $source;
        }
    }

    private function relativePath(string $path): string
    {
        return ltrim(substr($path, strlen($this->root)), DIRECTORY_SEPARATOR);
    }
}

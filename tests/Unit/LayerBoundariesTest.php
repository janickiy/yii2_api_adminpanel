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

    public function testDomainDoesNotDependOnOuterLayersOrYii(): void
    {
        $this->assertLayerDoesNotReference('domain', [
            'application\\',
            'infrastructure\\',
            'frontend\\',
            'backend\\',
            'common\\',
            'yii\\',
            'Yii::',
        ]);
    }

    public function testApplicationDependsOnlyOnDomainAndPhp(): void
    {
        $this->assertLayerDoesNotReference('application', [
            'infrastructure\\',
            'frontend\\',
            'backend\\',
            'common\\',
            'yii\\',
            'Yii::',
        ]);
    }

    /** @param list<string> $forbiddenReferences */
    private function assertLayerDoesNotReference(string $layer, array $forbiddenReferences): void
    {
        foreach ($this->phpFiles($this->root . '/' . $layer) as $path => $source) {
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

            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);

            yield $file->getPathname() => $source;
        }
    }

    private function relativePath(string $path): string
    {
        return ltrim(substr($path, strlen($this->root)), DIRECTORY_SEPARATOR);
    }
}

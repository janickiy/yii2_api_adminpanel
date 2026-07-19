<?php

declare(strict_types=1);

namespace tests\Unit;

use common\entities\Admin;
use common\entities\Category;
use common\entities\Note;
use common\entities\User;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use yii\web\IdentityInterface;

final class EntitiesTest extends TestCase
{
    public function testRelationsHaveOneCanonicalActiveRecordOwner(): void
    {
        self::assertMethodDeclaredBy(Category::class, 'getNotes');
        self::assertMethodDeclaredBy(Note::class, 'getUser');
        self::assertMethodDeclaredBy(Note::class, 'getCategory');
        self::assertMethodDeclaredBy(User::class, 'getNotes');
    }

    public function testLegacyRecordAndModelFilesAreRemoved(): void
    {
        $root = dirname(__DIR__, 2);

        foreach (
            [
                'common/models/Catalog.php',
                'common/models/Notes.php',
                'common/models/User.php',
                'common/models/Admin.php',
                'common/models/Message.php',
                'infra' . 'structure/persistence/records/Category' . 'Record.php',
                'infra' . 'structure/persistence/records/Note' . 'Record.php',
                'infra' . 'structure/persistence/records/User' . 'Record.php',
            ] as $legacyPath
        ) {
            self::assertFileDoesNotExist($root . '/' . $legacyPath);
        }
    }

    public function testEntitiesDoNotContainYiiIdentityIntegration(): void
    {
        self::assertFalse(is_subclass_of(User::class, IdentityInterface::class));
        self::assertFalse(is_subclass_of(Admin::class, IdentityInterface::class));
    }

    /** @param class-string $class */
    private static function assertMethodDeclaredBy(string $class, string $method): void
    {
        self::assertSame(
            $class,
            (new ReflectionMethod($class, $method))->getDeclaringClass()->getName(),
        );
    }
}

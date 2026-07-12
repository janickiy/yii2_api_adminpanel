<?php

declare(strict_types=1);

namespace app\tests\Unit;

use backend\controllers\SiteController;
use backend\widgets\Alert;
use Codeception\Test\Unit;
use common\models\Admin;
use common\models\User;
use console\controllers\HelloController;
use frontend\controllers\SiteController as FrontendSiteController;
use frontend\modules\api\controllers\AuthController;
use frontend\modules\api\Module as ApiModule;
use Yii;
use yii\helpers\Url;

final class AdvancedStructureTest extends Unit
{
    public function testApplicationAliasesPointToAdvancedDirectories(): void
    {
        self::assertSame(dirname(__DIR__, 2) . '/backend', Yii::getAlias('@backend'));
        self::assertSame(dirname(__DIR__, 2) . '/frontend', Yii::getAlias('@frontend'));
        self::assertSame(dirname(__DIR__, 2) . '/frontend/modules/api', Yii::getAlias('@api'));
        self::assertSame(dirname(__DIR__, 2) . '/common', Yii::getAlias('@common'));
        self::assertSame(dirname(__DIR__, 2) . '/console', Yii::getAlias('@console'));
    }

    public function testMovedClassesAreAutoloadable(): void
    {
        self::assertTrue(class_exists(Admin::class));
        self::assertTrue(class_exists(User::class));
        self::assertTrue(class_exists(SiteController::class));
        self::assertTrue(class_exists(FrontendSiteController::class));
        self::assertTrue(class_exists(AuthController::class));
        self::assertTrue(class_exists(ApiModule::class));
        self::assertTrue(class_exists(HelloController::class));
        self::assertTrue(class_exists(Alert::class));
    }

    public function testBackendRoutesAreScopedUnderCp(): void
    {
        self::assertSame('/cp', Url::to(['/dashboard/index']));
        self::assertSame('/cp/admin', Url::to(['/admin/index']));
        self::assertSame('/cp/admin/create', Url::to(['/admin/create']));
        self::assertSame('/cp/admin/edit/1', Url::to(['/admin/edit', 'id' => 1]));
        self::assertSame('/cp/catalog', Url::to(['/catalog/index']));
        self::assertSame('/cp/notes', Url::to(['/notes/index']));
        self::assertSame('/cp/datatable/notes', Url::to(['/datatable/notes']));
        self::assertSame('/login', Url::to(['/site/login']));
    }
}

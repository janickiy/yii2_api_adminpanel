<?php

declare(strict_types=1);

namespace frontend\modules\api;

use yii\base\BootstrapInterface;
use yii\web\Application;

final class Module extends \yii\base\Module implements BootstrapInterface
{
    /** @var array<string, string> */
    public const URL_RULES = [
        'GET api/documentation' => 'api/documentation/index',
        'GET docs' => 'api/documentation/spec',

        'GET api/v1' => 'api/site/index',
        'POST api/v1/register' => 'api/auth/register',
        'POST api/v1/login' => 'api/auth/login',
        'POST api/v1/logout' => 'api/auth/logout',

        'GET api/v1/notes' => 'api/note/index',
        'POST api/v1/notes' => 'api/note/create',
        'GET api/v1/notes/<id:\d+>' => 'api/note/show',
        'PUT,PATCH api/v1/notes/<id:\d+>' => 'api/note/update',
        'DELETE api/v1/notes/<id:\d+>' => 'api/note/delete',
        'GET api/v1/categories' => 'api/category/index',

        // Route unsupported verbs to the same controllers so VerbFilter returns 405.
        'api/v1' => 'api/site/index',
        'api/v1/register' => 'api/auth/register',
        'api/v1/login' => 'api/auth/login',
        'api/v1/logout' => 'api/auth/logout',
        'api/v1/notes' => 'api/note/index',
        'api/v1/notes/<id:\d+>' => 'api/note/show',
        'api/v1/categories' => 'api/category/index',
    ];

    public $controllerNamespace = 'frontend\modules\api\controllers';

    public function bootstrap($app): void
    {
        if (!$app instanceof Application) {
            return;
        }

        $app->getUrlManager()->addRules(self::URL_RULES, false);
    }
}

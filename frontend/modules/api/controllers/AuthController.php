<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use common\filters\PublicRateLimitFilter;
use frontend\modules\api\handlers\AuthRequestHandler;
use Yii;
use yii\base\Module;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

final class AuthController extends BaseApiController
{
    public function __construct(
        string $id,
        Module $module,
        private readonly AuthRequestHandler $handler,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'register' => ['POST'],
                'login' => ['POST'],
                'logout' => ['POST'],
            ],
        ];
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'user' => Yii::$app->get('apiUser'),
            'except' => ['register', 'login'],
        ];
        $behaviors['publicRateLimit'] = [
            'class' => PublicRateLimitFilter::class,
            'only' => ['register', 'login'],
            'limit' => 10,
            'window' => 60,
            'scope' => 'api-auth',
        ];

        return $behaviors;
    }

    public function actionRegister(): array
    {
        return $this->handler->register();
    }

    public function actionLogin(): array
    {
        return $this->handler->login();
    }

    public function actionLogout(): null
    {
        return $this->handler->logout();
    }
}

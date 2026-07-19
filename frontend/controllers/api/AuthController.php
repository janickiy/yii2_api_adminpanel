<?php

declare(strict_types=1);

namespace frontend\controllers\api;

use common\filters\PublicRateLimitFilter;
use common\repositories\PersistenceException;
use common\services\AuthService;
use common\services\exceptions\AuthenticationException;
use common\services\exceptions\ConflictException;
use frontend\components\api\ApiExceptionMapper;
use frontend\components\api\ApiRequestContext;
use frontend\components\api\ApiResponder;
use frontend\components\api\RequestInputFactory;
use frontend\forms\api\LoginInput;
use frontend\forms\api\RegisterInput;
use Yii;
use yii\base\Module;
use yii\filters\auth\HttpBearerAuth;

final class AuthController extends BaseApiController
{
    public function __construct(
        string $id,
        Module $module,
        private readonly AuthService $authService,
        private readonly RequestInputFactory $requests,
        private readonly ApiRequestContext $context,
        private readonly ApiResponder $responder,
        private readonly ApiExceptionMapper $exceptions,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
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

    protected function verbs(): array
    {
        return [
            'register' => ['POST'],
            'login' => ['POST'],
            'logout' => ['POST'],
        ];
    }

    public function actionRegister(): array
    {
        $dto = $this->requests->fromBody(RegisterInput::class)->toDto();

        try {
            $user = $this->authService->register($dto);
        } catch (ConflictException $exception) {
            $this->exceptions->conflict($exception);
        } catch (PersistenceException $exception) {
            $this->exceptions->persistence($exception, 'Unable to create the user.');
        }

        return $this->responder->user($user, 201);
    }

    public function actionLogin(): array
    {
        $dto = $this->requests->fromBody(LoginInput::class)->toDto();

        try {
            $result = $this->authService->login($dto);
        } catch (AuthenticationException $exception) {
            $this->exceptions->authentication($exception);
        }

        return $this->responder->authentication($result);
    }

    public function actionLogout(): null
    {
        try {
            $this->authService->logout($this->context->bearerToken());
        } catch (AuthenticationException $exception) {
            $this->exceptions->authentication($exception);
        }

        return $this->responder->noContent();
    }
}

<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use application\dto\auth\LoginUserDto;
use application\dto\auth\RegisterUserDto;
use application\services\AuthService;
use common\filters\PublicRateLimitFilter;
use domain\exceptions\AuthenticationException;
use domain\exceptions\ConflictException;
use domain\exceptions\PersistenceException;
use frontend\modules\api\presenters\ApiPresenter;
use OpenApi\Attributes as OA;
use Yii;
use yii\base\Module;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\ConflictHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnauthorizedHttpException;

final class AuthController extends BaseApiController
{
    public function __construct(
        string $id,
        Module $module,
        private readonly AuthService $authService,
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

    #[OA\Post(
        path: '/api/v1/register',
        operationId: 'register',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 201, description: 'User created'),
            new OA\Response(response: 409, description: 'Email already exists'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ],
    )]
    public function actionRegister(): array
    {
        $dto = new RegisterUserDto();
        $dto->load($this->bodyParams(), '');
        if (!$dto->validate()) {
            return $this->validationResponse($dto);
        }

        try {
            $user = $this->authService->register($dto);
        } catch (ConflictException $exception) {
            throw new ConflictHttpException($exception->getMessage(), 0, $exception);
        } catch (PersistenceException $exception) {
            Yii::error([
                'event' => 'auth.register.persistence_error',
                'exception_class' => $exception::class,
            ], 'application.api');
            throw new ServerErrorHttpException('Unable to create the user.', 0, $exception);
        }

        Yii::$app->response->statusCode = 201;

        return ['data' => ApiPresenter::user($user)];
    }

    #[OA\Post(
        path: '/api/v1/login',
        operationId: 'login',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Authenticated'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ],
    )]
    public function actionLogin(): array
    {
        $dto = new LoginUserDto();
        $dto->load($this->bodyParams(), '');
        if (!$dto->validate()) {
            return $this->validationResponse($dto);
        }

        try {
            $result = $this->authService->login($dto);
        } catch (AuthenticationException $exception) {
            throw new UnauthorizedHttpException($exception->getMessage(), 0, $exception);
        }

        return [
            'data' => [
                'token' => $result->token,
                'token_type' => 'Bearer',
                'user' => ApiPresenter::user($result->user),
            ],
        ];
    }

    #[OA\Post(
        path: '/api/v1/logout',
        operationId: 'logout',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 204, description: 'Token revoked'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ],
    )]
    public function actionLogout(): null
    {
        try {
            $this->authService->logout($this->bearerToken());
        } catch (AuthenticationException $exception) {
            throw new UnauthorizedHttpException($exception->getMessage(), 0, $exception);
        }

        Yii::$app->response->statusCode = 204;

        return null;
    }
}

<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use common\models\forms\LoginForm;
use common\models\forms\RegisterForm;
use common\models\User;
use OpenApi\Attributes as OA;
use Throwable;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class AuthController extends BaseApiController
{
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

        return $behaviors;
    }

    #[OA\Post(
        path: '/api/v1/register',
        operationId: 'authRegister',
        summary: 'Регистрация нового пользователя',
        description: 'Создает новый аккаунт пользователя.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'confirm_password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Иван Иванов'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Secret123!'),
                    new OA\Property(property: 'confirm_password', type: 'string', format: 'password', example: 'Secret123!'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Пользователь успешно создан',
                content: new OA\JsonContent(ref: '#/components/schemas/User'),
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибка валидации',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function actionRegister(): array|User
    {
        $form = new RegisterForm();
        $form->load($this->bodyParams(), '');

        if (!$form->validate()) {
            return $this->validationResponse($form);
        }

        $user = new User([
            'name' => $form->name,
            'email' => $form->email,
        ]);
        $user->setPassword((string) $form->password);
        $user->save(false);

        Yii::$app->response->statusCode = 201;

        return $user;
    }

    #[OA\Post(
        path: '/api/v1/login',
        operationId: 'authLogin',
        summary: 'Авторизация пользователя',
        description: 'Проверяет учетные данные и выдает JWT Bearer token.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Secret123!'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный вход',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Неверный логин или пароль',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибка валидации',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function actionLogin(): array
    {
        $form = new LoginForm();
        $form->load($this->bodyParams(), '');

        if (!$form->validate()) {
            Yii::$app->response->statusCode = 401;

            return ['error' => 'Invalid credentials'];
        }

        return ['token' => $form->getUser()?->generateAccessToken()];
    }

    #[OA\Post(
        path: '/api/v1/logout',
        operationId: 'authLogout',
        summary: 'Выход пользователя',
        description: 'Добавляет текущий JWT в blacklist до истечения срока действия.',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Токен успешно отозван',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully logged out'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
        ],
    )]
    public function actionLogout(): array
    {
        $token = $this->bearerToken();

        if ($token !== null) {
            try {
                User::revokeAccessToken($token);
            } catch (Throwable) {
            }
        }

        return ['message' => 'Successfully logged out'];
    }
}

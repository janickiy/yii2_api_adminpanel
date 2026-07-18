<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use Yii;
use yii\base\Model;
use yii\filters\ContentNegotiator;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\User;

abstract class BaseApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        return $behaviors;
    }

    /** @return array<string, mixed> */
    protected function bodyParams(): array
    {
        $params = Yii::$app->request->getBodyParams();

        return is_array($params) ? $params : [];
    }

    /** @return array{error: array{status: int, message: string, fields: array<string, list<string>>}} */
    protected function validationResponse(Model $model): array
    {
        Yii::$app->response->statusCode = 422;

        return [
            'error' => [
                'status' => 422,
                'message' => 'Validation failed.',
                'fields' => $model->getErrors(),
            ],
        ];
    }

    /** @return array{error: array{status: int, message: string, fields: array<string, list<string>>}} */
    protected function fieldError(string $field, string $message): array
    {
        Yii::$app->response->statusCode = 422;

        return [
            'error' => [
                'status' => 422,
                'message' => 'Validation failed.',
                'fields' => [$field => [$message]],
            ],
        ];
    }

    protected function bearerToken(): string
    {
        $header = Yii::$app->request->headers->get('Authorization', '');

        return preg_match('/^Bearer\s+(\S+)$/i', $header, $matches) === 1
            ? $matches[1]
            : '';
    }

    protected function apiUser(): User
    {
        $user = Yii::$app->get('apiUser');

        if (!$user instanceof User) {
            throw new \RuntimeException('API user component is not configured.');
        }

        return $user;
    }

    protected function apiUserId(): int
    {
        return (int) $this->apiUser()->id;
    }
}

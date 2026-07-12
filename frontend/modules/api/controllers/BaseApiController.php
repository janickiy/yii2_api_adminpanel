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

    /**
     * @return array<string, mixed>
     */
    protected function bodyParams(): array
    {
        return Yii::$app->request->getBodyParams();
    }

    /**
     * @return array{message: string, errors: array<string, mixed>}
     */
    protected function validationResponse(Model $model): array
    {
        Yii::$app->response->statusCode = 422;

        return [
            'message' => 'Validation failed.',
            'errors' => $model->getErrors(),
        ];
    }

    protected function bearerToken(): ?string
    {
        $header = Yii::$app->request->headers->get('Authorization', '');

        if (preg_match('/^Bearer\s+(.*?)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
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

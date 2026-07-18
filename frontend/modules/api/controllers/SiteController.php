<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use OpenApi\Attributes as OA;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    public $enableCsrfValidation = false;

    #[OA\Get(
        path: '/api/v1/',
        operationId: 'apiRoot',
        summary: 'API root',
        description: 'Возвращает базовую информацию об API и ссылку на документацию.',
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Информация об API',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Yii2 API'),
                        new OA\Property(property: 'documentation', type: 'string', example: '/api/documentation'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function actionIndex(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'name' => 'Notes Service API',
            'version' => '1.0.0',
            'documentation' => '/api/documentation',
            'resources' => [
                'auth' => ['/api/v1/register', '/api/v1/login', '/api/v1/logout'],
                'notes' => '/api/v1/notes',
                'categories' => '/api/v1/categories',
            ],
        ];
    }

    public function actionError(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'error' => [
                'status' => Yii::$app->response->statusCode,
                'message' => 'API request failed.',
            ],
        ];
    }
}

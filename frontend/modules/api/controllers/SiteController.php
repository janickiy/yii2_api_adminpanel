<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class SiteController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'verbFilter' => [
                'class' => VerbFilter::class,
                'actions' => ['index' => ['GET']],
            ],
        ];
    }

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
}

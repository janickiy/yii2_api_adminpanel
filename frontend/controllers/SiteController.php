<?php

declare(strict_types=1);

namespace frontend\controllers;

use Yii;
use yii\web\Controller;

class SiteController extends Controller
{
    public function actionIndex(): string
    {
        return $this->render('index', [
            'apiUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/api/v1']),
        ]);
    }

    public function actionError(): string
    {
        $exception = Yii::$app->errorHandler->exception;

        return $this->render('error', [
            'message' => $exception?->getMessage() ?? 'Error',
        ]);
    }
}

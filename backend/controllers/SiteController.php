<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\forms\AdminLoginForm;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    public function actionHome(): Response
    {
        return $this->redirect(['/dashboard/index']);
    }

    public function actionLogin(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/dashboard/index']);
        }

        $model = new AdminLoginForm();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post(), '');

            if ($model->login()) {
                return $this->redirect(['/dashboard/index']);
            }

            Yii::$app->session->setFlash('error', 'Неверный логин или пароль!');
        }

        $this->layout = false;

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->redirect(['/site/login']);
    }

    public function actionError(): array|string
    {
        $exception = Yii::$app->errorHandler->exception;

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return [
                'message' => $exception?->getMessage() ?? 'Error',
            ];
        }

        $name = $exception !== null && method_exists($exception, 'getName')
            ? $exception->getName()
            : 'Error';

        return $this->render('error', [
            'name' => $name,
            'message' => $exception?->getMessage() ?? 'Error',
            'exception' => $exception,
        ]);
    }
}

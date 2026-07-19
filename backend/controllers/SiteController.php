<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\forms\AdminLoginForm;
use common\filters\PublicRateLimitFilter;
use Yii;
use yii\base\UserException;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class SiteController extends Controller
{
    public $layout = 'error';

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'home' => ['GET'],
                    'login' => ['GET', 'POST'],
                    'logout' => ['POST'],
                ],
            ],
            'publicRateLimit' => [
                'class' => PublicRateLimitFilter::class,
                'only' => ['login'],
                'limit' => 10,
                'window' => 60,
                'scope' => 'admin-auth',
            ],
        ];
    }

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
                Yii::info([
                    'event' => 'admin.login',
                    'admin_id' => (int) Yii::$app->user->id,
                ], 'application.admin');

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
        $adminId = (int) Yii::$app->user->id;
        Yii::$app->user->logout();
        Yii::info(['event' => 'admin.logout', 'admin_id' => $adminId], 'application.admin');

        return $this->redirect(['/site/login']);
    }

    public function actionError(): array|string
    {
        $exception = Yii::$app->errorHandler->exception;
        $safeMessage = $exception instanceof UserException || YII_DEBUG
            ? ($exception?->getMessage() ?? 'Ошибка')
            : 'Произошла внутренняя ошибка.';

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return [
                'message' => $safeMessage,
            ];
        }

        $name = $exception !== null && method_exists($exception, 'getName')
            ? $exception->getName()
            : 'Error';

        return $this->render('error', [
            'name' => $name,
            'message' => $safeMessage,
            'exception' => $exception,
        ]);
    }
}

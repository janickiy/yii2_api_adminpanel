<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\components\AdminIdentity;
use backend\forms\AdminLoginForm;
use common\filters\PublicRateLimitFilter;
use common\repositories\PersistenceException;
use common\services\AdminService;
use common\services\exceptions\AuthenticationException;
use Yii;
use yii\base\Module;
use yii\base\UserException;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

final class SiteController extends Controller
{
    public $layout = 'error';

    public function __construct(
        string $id,
        Module $module,
        private readonly AdminService $admins,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

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

            if ($model->validate()) {
                try {
                    $admin = $this->admins->authenticate($model->toDto());
                    $duration = $model->remember ? 3600 * 24 * 30 : 0;
                    if (Yii::$app->user->login(AdminIdentity::fromEntity($admin), $duration)) {
                        return $this->redirect(['/dashboard/index']);
                    }

                    $model->addError('password', 'Не удалось войти в систему.');
                } catch (AuthenticationException) {
                    $model->addError('password', 'Неверный логин или пароль.');
                } catch (PersistenceException $exception) {
                    $this->throwPersistenceError($exception, 'Не удалось выполнить вход.');
                }
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
        $this->admins->logLogout($adminId);

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

    private function throwPersistenceError(
        PersistenceException $exception,
        string $message,
    ): never {
        Yii::error([
            'event' => 'admin.persistence_failed',
            'exception_class' => $exception::class,
        ], 'application.admin');

        throw new ServerErrorHttpException($message, 0, $exception);
    }
}

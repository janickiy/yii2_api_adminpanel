<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\components\AdminIdentity;
use common\entities\Admin;
use common\repositories\PersistenceException;
use common\services\AdminService;
use common\services\exceptions\NotFoundException;
use Yii;
use yii\base\Module;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

abstract class BaseWebController extends Controller
{
    public $layout = 'admin';

    protected string $permissions = '';

    public function __construct(
        string $id,
        Module $module,
        private readonly AdminService $access,
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
                    'destroy' => ['DELETE'],
                ],
            ],
        ];
    }

    public function beforeAction($action): bool
    {
        if (Yii::$app->user->isGuest) {
            $this->redirect(['/site/login'])->send();

            return false;
        }

        if ($this->permissions !== '' && !$this->canAccess($this->permissions)) {
            throw new ForbiddenHttpException('Доступ запрещен.');
        }

        return parent::beforeAction($action);
    }

    protected function admin(): Admin
    {
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof AdminIdentity) {
            throw new ForbiddenHttpException('Доступ запрещен.');
        }

        return $identity->entity();
    }

    protected function canAccess(string $permissions): bool
    {
        return $this->access->canAccess($this->admin(), $permissions);
    }

    /**
     * @template T of \yii\db\ActiveRecord
     * @param ActiveQuery<T> $query
     */
    protected function dataProvider(ActiveQuery $query, int $pageSize = 20): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $pageSize],
        ]);
    }

    protected function throwNotFound(NotFoundException $exception, string $message): never
    {
        throw new NotFoundHttpException($message, 0, $exception);
    }

    /**
     * @param callable(): void $delete
     * @param array<int|string, mixed> $redirect
     */
    protected function deleteAndRespond(
        callable $delete,
        string $message,
        array $redirect,
    ): Response|array {
        try {
            $delete();
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось удалить запись.');
        }

        return $this->successResponse($message, $redirect);
    }

    /**
     * @param array<int|string, mixed> $redirect
     */
    protected function successResponse(string $message, array $redirect): Response|array
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return ['message' => $message];
        }

        Yii::$app->session->setFlash('success', $message);

        return $this->redirect($redirect);
    }

    /**
     * @param array<int|string, mixed> $redirect
     */
    protected function rejectedResponse(
        string $message,
        array $redirect,
        int $statusCode = 403,
    ): Response|array {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->statusCode = $statusCode;

            return ['message' => $message];
        }

        Yii::$app->session->setFlash('error', $message);

        return $this->redirect($redirect);
    }

    protected function throwPersistenceError(
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

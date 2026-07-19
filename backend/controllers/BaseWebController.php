<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use domain\exceptions\PersistenceException;
use Yii;
use yii\db\ActiveRecord;
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

        if ($this->permissions !== '' && !$this->admin()->canAccess($this->permissions)) {
            throw new ForbiddenHttpException('Доступ запрещен.');
        }

        return parent::beforeAction($action);
    }

    protected function admin(): Admin
    {
        /** @var Admin $identity */
        $identity = Yii::$app->user->identity;

        return $identity;
    }

    /**
     * @template T of ActiveRecord
     * @param class-string<T> $recordClass
     * @return T
     */
    protected function findRecord(
        string $recordClass,
        int $id,
        string $message = 'Запись не найдена.',
    ): ActiveRecord {
        $record = $recordClass::findOne($id);

        if (!$record instanceof $recordClass) {
            throw new NotFoundHttpException($message);
        }

        return $record;
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

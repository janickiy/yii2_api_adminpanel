<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use common\models\Message;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class MessagesController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'status' => ['POST'],
                'destroy' => ['DELETE'],
            ],
        ];

        return $behaviors;
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Сообщения',
            'dataProvider' => new ActiveDataProvider([
                'query' => Message::find()->orderBy(['created_at' => SORT_DESC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionView(int $id): string
    {
        $message = $this->findModel($id);
        if ($message->status === Message::STATUS_NEW) {
            if (!$message->markAs(Message::STATUS_READ)) {
                throw new \yii\web\ServerErrorHttpException('Не удалось изменить статус сообщения.');
            }
            Yii::info(['event' => 'message.read', 'message_id' => $id], 'application.admin');
        }

        return $this->render('view', [
            'title' => 'Сообщение #' . $message->id,
            'model' => $message,
        ]);
    }

    public function actionStatus(int $id): Response
    {
        $message = $this->findModel($id);
        $status = (string) Yii::$app->request->post(
            'status',
            Yii::$app->request->get('status', ''),
        );

        if (!$message->markAs($status)) {
            throw new BadRequestHttpException('Неизвестный статус сообщения.');
        }

        Yii::info([
            'event' => 'message.status_changed',
            'message_id' => $id,
            'status' => $status,
        ], 'application.admin');
        Yii::$app->session->setFlash('success', 'Статус сообщения изменён.');

        return $this->redirect(Yii::$app->request->referrer ?: ['/messages/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $this->deleteRecord($this->findModel($id));
        Yii::info(['event' => 'message.deleted', 'message_id' => $id], 'application.admin');

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return ['message' => 'Сообщение удалено.'];
        }

        Yii::$app->session->setFlash('success', 'Сообщение удалено.');

        return $this->redirect(['/messages/index']);
    }

    private function findModel(int $id): Message
    {
        $model = Message::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Сообщение не найдено.');
        }

        return $model;
    }
}

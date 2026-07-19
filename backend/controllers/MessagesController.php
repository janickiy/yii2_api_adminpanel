<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\services\MessageManagementService;
use common\models\Admin;
use common\models\Message;
use domain\exceptions\PersistenceException;
use InvalidArgumentException;
use Yii;
use yii\base\Module;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Response;

final class MessagesController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR;

    public function __construct(
        string $id,
        Module $module,
        private readonly MessageManagementService $messages,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

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
        $message = $this->findRecord(Message::class, $id, 'Сообщение не найдено.');

        try {
            $this->messages->markAsRead($message);
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось изменить статус сообщения.');
        }

        return $this->render('view', [
            'title' => 'Сообщение #' . $message->id,
            'model' => $message,
        ]);
    }

    public function actionStatus(int $id): Response
    {
        $message = $this->findRecord(Message::class, $id, 'Сообщение не найдено.');
        $status = (string) Yii::$app->request->post(
            'status',
            Yii::$app->request->get('status', ''),
        );

        try {
            $this->messages->changeStatus($message, $status);
        } catch (InvalidArgumentException) {
            throw new BadRequestHttpException('Неизвестный статус сообщения.');
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось изменить статус сообщения.');
        }

        Yii::$app->session->setFlash('success', 'Статус сообщения изменён.');

        return $this->redirect(Yii::$app->request->referrer ?: ['/messages/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $message = $this->findRecord(Message::class, $id, 'Сообщение не найдено.');

        return $this->deleteAndRespond(
            function () use ($message): void {
                $this->messages->delete($message);
            },
            'Сообщение удалено.',
            ['/messages/index'],
        );
    }
}

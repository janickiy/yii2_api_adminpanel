<?php

declare(strict_types=1);

namespace backend\controllers;

use common\entities\Admin;
use common\entities\Message;
use common\repositories\PersistenceException;
use common\services\AdminService;
use common\services\exceptions\NotFoundException;
use common\services\MessageService;
use InvalidArgumentException;
use Yii;
use yii\base\Module;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Response;

final class MessagesController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR;

    public function __construct(
        string $id,
        Module $module,
        private readonly MessageService $messages,
        AdminService $access,
        array $config = [],
    ) {
        parent::__construct($id, $module, $access, $config);
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
            'dataProvider' => $this->dataProvider($this->messages->query()),
        ]);
    }

    public function actionView(int $id): string
    {
        $message = $this->getMessage($id);

        return $this->render('view', [
            'title' => 'Сообщение #' . $message->id,
            'model' => $message,
        ]);
    }

    public function actionStatus(int $id): Response
    {
        $message = $this->getMessage($id);
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
        $message = $this->getMessage($id);

        return $this->deleteAndRespond(
            function () use ($message): void {
                $this->messages->delete($message);
            },
            'Сообщение удалено.',
            ['/messages/index'],
        );
    }

    private function getMessage(int $id): Message
    {
        try {
            return $this->messages->get($id);
        } catch (NotFoundException $exception) {
            $this->throwNotFound($exception, 'Сообщение не найдено.');
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось получить сообщение.');
        }
    }
}

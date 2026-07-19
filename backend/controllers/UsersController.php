<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\forms\UserForm;
use backend\services\UserManagementService;
use common\models\Admin;
use infrastructure\persistence\records\UserRecord;
use Yii;
use yii\base\Module;
use yii\data\ActiveDataProvider;
use yii\web\Response;

final class UsersController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN;

    public function __construct(
        string $id,
        Module $module,
        private readonly UserManagementService $users,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Пользователи',
            'dataProvider' => new ActiveDataProvider([
                'query' => UserRecord::find()->orderBy(['id' => SORT_DESC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionCreate(): string
    {
        return $this->renderForm(
            'Добавить пользователя',
            new UserForm(['scenario' => UserForm::SCENARIO_CREATE]),
        );
    }

    public function actionStore(): Response|string
    {
        $form = new UserForm(['scenario' => UserForm::SCENARIO_CREATE]);
        $form->load(Yii::$app->request->post());

        if (!$form->validate() || !$this->users->create($form)) {
            return $this->renderForm('Добавить пользователя', $form);
        }

        Yii::$app->session->setFlash('success', 'Пользователь создан.');

        return $this->redirect(['/users/index']);
    }

    public function actionEdit(int $id): string
    {
        $user = $this->findRecord(UserRecord::class, $id, 'Пользователь не найден.');
        $form = new UserForm(['scenario' => UserForm::SCENARIO_UPDATE]);
        $form->loadFromUser($user);

        return $this->renderForm('Редактировать пользователя', $form);
    }

    public function actionUpdate(int $id): Response|string
    {
        $form = new UserForm(['scenario' => UserForm::SCENARIO_UPDATE]);
        $form->load(Yii::$app->request->post());
        $form->id = $id;
        $user = $this->findRecord(
            UserRecord::class,
            (int) $form->id,
            'Пользователь не найден.',
        );

        if (!$form->validate() || !$this->users->update($user, $form)) {
            return $this->renderForm('Редактировать пользователя', $form);
        }

        Yii::$app->session->setFlash('success', 'Пользователь обновлён.');

        return $this->redirect(['/users/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $user = $this->findRecord(UserRecord::class, $id, 'Пользователь не найден.');

        return $this->deleteAndRespond(
            function () use ($user): void {
                $this->users->delete($user);
            },
            'Пользователь удалён.',
            ['/users/index'],
        );
    }

    private function renderForm(string $title, UserForm $model): string
    {
        return $this->render('form', [
            'title' => $title,
            'model' => $model,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\forms\UserForm;
use common\entities\Admin;
use common\entities\User;
use common\repositories\PersistenceException;
use common\services\AdminService;
use common\services\exceptions\ConflictException;
use common\services\exceptions\NotFoundException;
use common\services\UserService;
use Yii;
use yii\base\Module;
use yii\web\Response;

final class UsersController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN;

    public function __construct(
        string $id,
        Module $module,
        private readonly UserService $users,
        AdminService $access,
        array $config = [],
    ) {
        parent::__construct($id, $module, $access, $config);
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Пользователи',
            'dataProvider' => $this->dataProvider($this->users->query()),
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

        if (!$form->validate()) {
            return $this->renderForm('Добавить пользователя', $form);
        }

        try {
            $this->users->create($form->toDto());
        } catch (ConflictException) {
            $form->addError('email', 'Пользователь с таким email уже существует.');

            return $this->renderForm('Добавить пользователя', $form);
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось создать пользователя.');
        }

        Yii::$app->session->setFlash('success', 'Пользователь создан.');

        return $this->redirect(['/users/index']);
    }

    public function actionEdit(int $id): string
    {
        $user = $this->getUser($id);
        $form = new UserForm(['scenario' => UserForm::SCENARIO_UPDATE]);
        $form->loadFromUser($user);

        return $this->renderForm('Редактировать пользователя', $form);
    }

    public function actionUpdate(int $id): Response|string
    {
        $user = $this->getUser($id);
        $form = new UserForm(['scenario' => UserForm::SCENARIO_UPDATE]);
        $form->load(Yii::$app->request->post());
        $form->id = $id;

        if (!$form->validate()) {
            return $this->renderForm('Редактировать пользователя', $form);
        }

        try {
            $this->users->update($user, $form->toDto());
        } catch (ConflictException) {
            $form->addError('email', 'Пользователь с таким email уже существует.');

            return $this->renderForm('Редактировать пользователя', $form);
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось обновить пользователя.');
        }

        Yii::$app->session->setFlash('success', 'Пользователь обновлён.');

        return $this->redirect(['/users/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $user = $this->getUser($id);

        return $this->deleteAndRespond(
            function () use ($user): void {
                $this->users->delete($user);
            },
            'Пользователь удалён.',
            ['/users/index'],
        );
    }

    private function getUser(int $id): User
    {
        try {
            return $this->users->get($id);
        } catch (NotFoundException $exception) {
            $this->throwNotFound($exception, 'Пользователь не найден.');
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось получить пользователя.');
        }
    }

    private function renderForm(string $title, UserForm $model): string
    {
        return $this->render('form', [
            'title' => $title,
            'model' => $model,
        ]);
    }
}

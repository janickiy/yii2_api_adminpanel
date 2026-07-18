<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use common\models\forms\UserForm;
use common\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class UsersController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN;

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Пользователи',
            'dataProvider' => new ActiveDataProvider([
                'query' => User::find()->orderBy(['id' => SORT_DESC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionCreate(): string
    {
        return $this->render('form', [
            'title' => 'Добавить пользователя',
            'model' => new UserForm(['scenario' => UserForm::SCENARIO_CREATE]),
        ]);
    }

    public function actionStore(): Response|string
    {
        $form = new UserForm(['scenario' => UserForm::SCENARIO_CREATE]);
        $form->load(Yii::$app->request->post());

        if (!$form->validate()) {
            return $this->render('form', [
                'title' => 'Добавить пользователя',
                'model' => $form,
            ]);
        }

        $user = new User([
            'name' => $form->name,
            'email' => $form->email,
        ]);
        $user->setPassword((string) $form->password);
        if (!$user->save()) {
            $this->copyErrors($user, $form);

            return $this->render('form', [
                'title' => 'Добавить пользователя',
                'model' => $form,
            ]);
        }

        Yii::info(['event' => 'user.created.admin', 'user_id' => (int) $user->id], 'application.admin');
        Yii::$app->session->setFlash('success', 'Пользователь создан.');

        return $this->redirect(['/users/index']);
    }

    public function actionEdit(int $id): string
    {
        $user = $this->findModel($id);
        $form = new UserForm(['scenario' => UserForm::SCENARIO_UPDATE]);
        $form->loadFromUser($user);

        return $this->render('form', [
            'title' => 'Редактировать пользователя',
            'model' => $form,
        ]);
    }

    public function actionUpdate(?int $id = null): Response|string
    {
        $form = new UserForm(['scenario' => UserForm::SCENARIO_UPDATE]);
        $form->load(Yii::$app->request->post());
        if ($id !== null) {
            $form->id = $id;
        }
        $user = $this->findModel((int) $form->id);

        if (!$form->validate()) {
            return $this->render('form', [
                'title' => 'Редактировать пользователя',
                'model' => $form,
            ]);
        }

        $user->name = (string) $form->name;
        $user->email = (string) $form->email;
        if ($form->password !== null && $form->password !== '') {
            $user->setPassword($form->password);
        }
        if (!$user->save()) {
            $this->copyErrors($user, $form);

            return $this->render('form', [
                'title' => 'Редактировать пользователя',
                'model' => $form,
            ]);
        }

        Yii::info(['event' => 'user.updated.admin', 'user_id' => (int) $user->id], 'application.admin');
        Yii::$app->session->setFlash('success', 'Пользователь обновлён.');

        return $this->redirect(['/users/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $this->deleteRecord($this->findModel($id));
        Yii::info(['event' => 'user.deleted.admin', 'user_id' => $id], 'application.admin');

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return ['message' => 'Пользователь удалён.'];
        }

        Yii::$app->session->setFlash('success', 'Пользователь удалён.');

        return $this->redirect(['/users/index']);
    }

    private function findModel(int $id): User
    {
        $model = User::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }

        return $model;
    }
}

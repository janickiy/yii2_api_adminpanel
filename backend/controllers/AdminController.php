<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use common\models\forms\AdminForm;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdminController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN;

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Пользователи',
        ]);
    }

    public function actionCreate(): string
    {
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_CREATE]);
        $model->role = Admin::ROLE_ADMIN;

        return $this->render('form', [
            'title' => 'Добавить пользователя',
            'model' => $model,
            'roles' => Admin::roleLabels(),
        ]);
    }

    public function actionStore(): Response|string
    {
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_CREATE]);
        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            return $this->render('form', [
                'title' => 'Добавить пользователя',
                'model' => $model,
                'roles' => Admin::roleLabels(),
            ]);
        }

        $admin = new Admin([
            'login' => $model->login,
            'name' => $model->name,
            'role' => $model->role,
        ]);
        $admin->setPassword((string) $model->password);
        $admin->save(false);

        Yii::$app->session->setFlash('success', 'Информация успешно добавлена!');

        return $this->redirect(['/admin/index']);
    }

    public function actionEdit(int $id): string
    {
        $admin = $this->findModel($id);
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_UPDATE]);
        $model->loadFromAdmin($admin);

        return $this->render('form', [
            'title' => 'Редактировать пользователя',
            'model' => $model,
            'roles' => Admin::roleLabels(),
            'adminRecord' => $admin,
        ]);
    }

    public function actionUpdate(): Response|string
    {
        $id = (int) Yii::$app->request->post('id');
        $admin = $this->findModel($id);
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_UPDATE]);
        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            return $this->render('form', [
                'title' => 'Редактировать пользователя',
                'model' => $model,
                'roles' => Admin::roleLabels(),
                'adminRecord' => $admin,
            ]);
        }

        $admin->login = (string) $model->login;
        $admin->name = $model->name;

        if ((int) $admin->id !== (int) Yii::$app->user->id && $model->role !== null) {
            $admin->role = $model->role;
        }

        if ($model->password !== null && $model->password !== '') {
            $admin->setPassword($model->password);
        }

        $admin->save(false);
        Yii::$app->session->setFlash('success', 'Данные успешно обновлены!');

        return $this->redirect(['/admin/index']);
    }

    public function actionDestroy(int $id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($id === (int) Yii::$app->user->id) {
            Yii::$app->response->statusCode = 403;

            return ['message' => 'Нельзя удалить текущего пользователя.'];
        }

        $this->findModel($id)->delete();

        return ['message' => 'Данные успешно удалены.'];
    }

    private function findModel(int $id): Admin
    {
        $model = Admin::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException('Запись не найдена.');
        }

        return $model;
    }
}

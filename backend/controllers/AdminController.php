<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use common\models\forms\AdminForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdminController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN;

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Администраторы',
            'dataProvider' => new ActiveDataProvider([
                'query' => Admin::find()->orderBy(['id' => SORT_DESC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionCreate(): string
    {
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_CREATE]);
        $model->role = Admin::ROLE_ADMIN;

        return $this->render('form', [
            'title' => 'Добавить администратора',
            'model' => $model,
            'roles' => Admin::roleLabels(),
        ]);
    }

    public function actionStore(): Response|string
    {
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_CREATE]);
        $model->load(Yii::$app->request->post());

        if (!$model->validate()) {
            return $this->render('form', [
                'title' => 'Добавить администратора',
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
        if (!$admin->save()) {
            $this->copyErrors($admin, $model);

            return $this->render('form', [
                'title' => 'Добавить администратора',
                'model' => $model,
                'roles' => Admin::roleLabels(),
            ]);
        }

        Yii::info(['event' => 'admin.created', 'admin_id' => (int) $admin->id], 'application.admin');
        Yii::$app->session->setFlash('success', 'Администратор создан.');

        return $this->redirect(['/admin/index']);
    }

    public function actionEdit(int $id): string
    {
        $admin = $this->findModel($id);
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_UPDATE]);
        $model->loadFromAdmin($admin);

        return $this->render('form', [
            'title' => 'Редактировать администратора',
            'model' => $model,
            'roles' => Admin::roleLabels(),
            'adminRecord' => $admin,
        ]);
    }

    public function actionUpdate(?int $id = null): Response|string
    {
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_UPDATE]);
        $model->load(Yii::$app->request->post());
        if ($id !== null) {
            $model->id = $id;
        }
        $admin = $this->findModel((int) $model->id);

        if (!$model->validate()) {
            return $this->render('form', [
                'title' => 'Редактировать администратора',
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

        if (!$admin->save()) {
            $this->copyErrors($admin, $model);

            return $this->render('form', [
                'title' => 'Редактировать администратора',
                'model' => $model,
                'roles' => Admin::roleLabels(),
                'adminRecord' => $admin,
            ]);
        }
        Yii::info(['event' => 'admin.updated', 'admin_id' => (int) $admin->id], 'application.admin');
        Yii::$app->session->setFlash('success', 'Данные администратора обновлены.');

        return $this->redirect(['/admin/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        if ($id === (int) Yii::$app->user->id) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->statusCode = 403;

                return ['message' => 'Нельзя удалить текущего администратора.'];
            }

            Yii::$app->session->setFlash('error', 'Нельзя удалить текущего администратора.');

            return $this->redirect(['/admin/index']);
        }

        $this->deleteRecord($this->findModel($id));
        Yii::info(['event' => 'admin.deleted', 'admin_id' => $id], 'application.admin');

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return ['message' => 'Администратор удалён.'];
        }

        Yii::$app->session->setFlash('success', 'Администратор удалён.');

        return $this->redirect(['/admin/index']);
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

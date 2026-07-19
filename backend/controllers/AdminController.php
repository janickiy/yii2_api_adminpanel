<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\forms\AdminForm;
use backend\services\AdminManagementService;
use common\models\Admin;
use domain\exceptions\PersistenceException;
use Yii;
use yii\base\Module;
use yii\data\ActiveDataProvider;
use yii\web\Response;

final class AdminController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN;

    public function __construct(
        string $id,
        Module $module,
        private readonly AdminManagementService $admins,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

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

        return $this->renderForm('Добавить администратора', $model);
    }

    public function actionStore(): Response|string
    {
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_CREATE]);
        $model->load(Yii::$app->request->post());

        if (!$model->validate() || !$this->admins->create($model)) {
            return $this->renderForm('Добавить администратора', $model);
        }

        Yii::$app->session->setFlash('success', 'Администратор создан.');

        return $this->redirect(['/admin/index']);
    }

    public function actionEdit(int $id): string
    {
        $admin = $this->findRecord(Admin::class, $id);
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_UPDATE]);
        $model->loadFromAdmin($admin);

        return $this->renderForm('Редактировать администратора', $model);
    }

    public function actionUpdate(int $id): Response|string
    {
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_UPDATE]);
        $model->load(Yii::$app->request->post());
        $model->id = $id;
        $admin = $this->findRecord(Admin::class, (int) $model->id);

        if (!$model->validate()) {
            return $this->renderForm('Редактировать администратора', $model);
        }

        $updated = $this->admins->update(
            $admin,
            $model,
            (int) Yii::$app->user->id,
        );
        if (!$updated) {
            return $this->renderForm('Редактировать администратора', $model);
        }

        Yii::$app->session->setFlash('success', 'Данные администратора обновлены.');

        return $this->redirect(['/admin/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $admin = $this->findRecord(Admin::class, $id);

        try {
            $deleted = $this->admins->delete($admin, (int) Yii::$app->user->id);
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось удалить администратора.');
        }

        if (!$deleted) {
            return $this->rejectedResponse(
                'Нельзя удалить текущего администратора.',
                ['/admin/index'],
            );
        }

        return $this->successResponse('Администратор удалён.', ['/admin/index']);
    }

    private function renderForm(string $title, AdminForm $model): string
    {
        return $this->render('form', [
            'title' => $title,
            'model' => $model,
            'roles' => Admin::roleLabels(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\forms\AdminForm;
use common\entities\Admin;
use common\repositories\PersistenceException;
use common\services\AdminService;
use common\services\exceptions\ConflictException;
use common\services\exceptions\NotFoundException;
use Yii;
use yii\base\Module;
use yii\web\Response;

final class AdminController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN;

    /**
     * @param string $id
     * @param Module $module
     * @param AdminService $admins
     * @param array $config
     */
    public function __construct(
        string $id,
        Module $module,
        private readonly AdminService $admins,
        array $config = [],
    ) {
        parent::__construct($id, $module, $admins, $config);
    }

    /**
     * @return string
     */
    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Администраторы',
            'dataProvider' => $this->dataProvider($this->admins->query()),
        ]);
    }

    /**
     * @return string
     */
    public function actionCreate(): string
    {
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_CREATE]);
        $model->role = Admin::ROLE_ADMIN;

        return $this->renderForm('Добавить администратора', $model);
    }

    /**
     * @return Response|string
     * @throws \yii\web\ServerErrorHttpException
     */
    public function actionStore(): Response|string
    {
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_CREATE]);
        $model->load(Yii::$app->request->post());

        if (!$model->validate()) {
            return $this->renderForm('Добавить администратора', $model);
        }

        try {
            $this->admins->create($model->toDto());
        } catch (ConflictException) {
            $model->addError('login', 'Администратор с таким логином уже существует.');

            return $this->renderForm('Добавить администратора', $model);
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось создать администратора.');
        }

        Yii::$app->session->setFlash('success', 'Администратор создан.');

        return $this->redirect(['/admin/index']);
    }

    /**
     * @param int $id
     * @return string
     */
    public function actionEdit(int $id): string
    {
        $admin = $this->getAdmin($id);
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_UPDATE]);
        $model->loadFromAdmin($admin);

        return $this->renderForm('Редактировать администратора', $model);
    }

    /**
     * @param int $id
     * @return Response|string
     * @throws \yii\web\ServerErrorHttpException
     */
    public function actionUpdate(int $id): Response|string
    {
        $admin = $this->getAdmin($id);
        $model = new AdminForm(['scenario' => AdminForm::SCENARIO_UPDATE]);
        $model->load(Yii::$app->request->post());
        $model->id = $id;

        if (!$model->validate()) {
            return $this->renderForm('Редактировать администратора', $model);
        }

        try {
            $this->admins->update($admin, $model->toDto(), (int) Yii::$app->user->id);
        } catch (ConflictException) {
            $model->addError('login', 'Администратор с таким логином уже существует.');

            return $this->renderForm('Редактировать администратора', $model);
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось обновить администратора.');
        }

        Yii::$app->session->setFlash('success', 'Данные администратора обновлены.');

        return $this->redirect(['/admin/index']);
    }

    /**
     * @param int $id
     * @return Response|array
     * @throws \yii\web\ServerErrorHttpException
     */
    public function actionDestroy(int $id): Response|array
    {
        $admin = $this->getAdmin($id);

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

    private function getAdmin(int $id): Admin
    {
        try {
            return $this->admins->get($id);
        } catch (NotFoundException $exception) {
            $this->throwNotFound($exception, 'Администратор не найден.');
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось получить администратора.');
        }
    }

    private function renderForm(string $title, AdminForm $model): string
    {
        return $this->render('form', [
            'title' => $title,
            'model' => $model,
            'roles' => AdminForm::roleLabels(),
        ]);
    }
}

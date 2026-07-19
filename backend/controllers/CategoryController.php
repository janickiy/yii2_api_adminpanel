<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\forms\CategoryForm;
use backend\services\CategoryManagementService;
use common\models\Admin;
use domain\exceptions\PersistenceException;
use infrastructure\persistence\records\CategoryRecord;
use Yii;
use yii\base\Module;
use yii\data\ActiveDataProvider;
use yii\web\Response;

final class CategoryController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR;

    public function __construct(
        string $id,
        Module $module,
        private readonly CategoryManagementService $categories,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Категории',
            'dataProvider' => new ActiveDataProvider([
                'query' => CategoryRecord::find()->orderBy(['name' => SORT_ASC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionCreate(): string
    {
        return $this->renderForm('Добавить категорию', new CategoryForm());
    }

    public function actionStore(): Response|string
    {
        $form = new CategoryForm();
        $form->load(Yii::$app->request->post());

        if (!$form->validate() || !$this->categories->create($form)) {
            return $this->renderForm('Добавить категорию', $form);
        }

        Yii::$app->session->setFlash('success', 'Категория создана.');

        return $this->redirect(['/category/index']);
    }

    public function actionEdit(int $id): string
    {
        $category = $this->findRecord(CategoryRecord::class, $id);
        $form = new CategoryForm();
        $form->loadFromCategory($category);

        return $this->renderForm('Редактирование категории', $form);
    }

    public function actionUpdate(int $id): Response|string
    {
        $form = new CategoryForm();
        $form->load(Yii::$app->request->post());
        $form->id = $id;
        $category = $this->findRecord(CategoryRecord::class, (int) $form->id);

        if (!$form->validate() || !$this->categories->update($category, $form)) {
            return $this->renderForm('Редактирование категории', $form);
        }

        Yii::$app->session->setFlash('success', 'Категория обновлена.');

        return $this->redirect(['/category/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $category = $this->findRecord(CategoryRecord::class, $id);

        try {
            $deleted = $this->categories->delete($category);
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось удалить категорию.');
        }

        if (!$deleted) {
            return $this->rejectedResponse(
                'Категория используется в заметках и не может быть удалена.',
                ['/category/index'],
                409,
            );
        }

        return $this->successResponse('Категория удалена.', ['/category/index']);
    }

    private function renderForm(string $title, CategoryForm $model): string
    {
        return $this->render('form', [
            'title' => $title,
            'model' => $model,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\forms\CategoryForm;
use common\entities\Admin;
use common\entities\Category;
use common\repositories\PersistenceException;
use common\services\AdminService;
use common\services\CategoryService;
use common\services\exceptions\ConflictException;
use common\services\exceptions\NotFoundException;
use Yii;
use yii\base\Module;
use yii\web\Response;

final class CategoryController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR;

    public function __construct(
        string $id,
        Module $module,
        private readonly CategoryService $categories,
        AdminService $access,
        array $config = [],
    ) {
        parent::__construct($id, $module, $access, $config);
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Категории',
            'dataProvider' => $this->dataProvider($this->categories->query()),
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

        if (!$form->validate()) {
            return $this->renderForm('Добавить категорию', $form);
        }

        try {
            $this->categories->create($form->toDto());
        } catch (ConflictException) {
            $form->addError('name', 'Категория с таким именем уже существует.');

            return $this->renderForm('Добавить категорию', $form);
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось создать категорию.');
        }

        Yii::$app->session->setFlash('success', 'Категория создана.');

        return $this->redirect(['/category/index']);
    }

    public function actionEdit(int $id): string
    {
        $category = $this->getCategory($id);
        $form = new CategoryForm();
        $form->loadFromCategory($category);

        return $this->renderForm('Редактирование категории', $form);
    }

    public function actionUpdate(int $id): Response|string
    {
        $category = $this->getCategory($id);
        $form = new CategoryForm();
        $form->load(Yii::$app->request->post());
        $form->id = $id;

        if (!$form->validate()) {
            return $this->renderForm('Редактирование категории', $form);
        }

        try {
            $this->categories->update($category, $form->toDto());
        } catch (ConflictException) {
            $form->addError('name', 'Категория с таким именем уже существует.');

            return $this->renderForm('Редактирование категории', $form);
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось обновить категорию.');
        }

        Yii::$app->session->setFlash('success', 'Категория обновлена.');

        return $this->redirect(['/category/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $category = $this->getCategory($id);

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

    private function getCategory(int $id): Category
    {
        try {
            return $this->categories->get($id);
        } catch (NotFoundException $exception) {
            $this->throwNotFound($exception, 'Категория не найдена.');
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось получить категорию.');
        }
    }

    private function renderForm(string $title, CategoryForm $model): string
    {
        return $this->render('form', [
            'title' => $title,
            'model' => $model,
        ]);
    }
}

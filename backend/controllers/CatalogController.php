<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use common\models\Catalog;
use common\models\forms\CatalogForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\IntegrityException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CatalogController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR;

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Категории',
            'dataProvider' => new ActiveDataProvider([
                'query' => Catalog::find()->orderBy(['name' => SORT_ASC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionCreate(): string
    {
        return $this->render('form', [
            'title' => 'Добавить категорию',
            'model' => new CatalogForm(),
        ]);
    }

    public function actionStore(): Response|string
    {
        $form = new CatalogForm();
        $form->load(Yii::$app->request->post());

        if (!$form->validate()) {
            return $this->render('form', [
                'title' => 'Добавить категорию',
                'model' => $form,
            ]);
        }

        $catalog = new Catalog(['name' => $form->name]);
        if (!$catalog->save()) {
            $this->copyErrors($catalog, $form);

            return $this->render('form', [
                'title' => 'Добавить категорию',
                'model' => $form,
            ]);
        }
        Yii::info(['event' => 'category.created', 'category_id' => (int) $catalog->id], 'application.admin');
        Yii::$app->session->setFlash('success', 'Категория создана.');

        return $this->redirect(['/catalog/index']);
    }

    public function actionEdit(int $id): string
    {
        $catalog = $this->findModel($id);
        $form = new CatalogForm([
            'id' => (int) $catalog->id,
            'name' => $catalog->name,
        ]);

        return $this->render('form', [
            'title' => 'Редактирование категории',
            'model' => $form,
        ]);
    }

    public function actionUpdate(?int $id = null): Response|string
    {
        $form = new CatalogForm();
        $form->load(Yii::$app->request->post());
        if ($id !== null) {
            $form->id = $id;
        }
        $catalog = $this->findModel((int) $form->id);

        if (!$form->validate()) {
            return $this->render('form', [
                'title' => 'Редактирование категории',
                'model' => $form,
            ]);
        }

        $catalog->name = (string) $form->name;
        if (!$catalog->save()) {
            $this->copyErrors($catalog, $form);

            return $this->render('form', [
                'title' => 'Редактирование категории',
                'model' => $form,
            ]);
        }
        Yii::info(['event' => 'category.updated', 'category_id' => (int) $catalog->id], 'application.admin');
        Yii::$app->session->setFlash('success', 'Категория обновлена.');

        return $this->redirect(['/catalog/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        try {
            $this->deleteRecord($this->findModel($id));
            Yii::info(['event' => 'category.deleted', 'category_id' => $id], 'application.admin');
        } catch (IntegrityException) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->statusCode = 409;

                return ['message' => 'Категория используется в заметках и не может быть удалена.'];
            }

            Yii::$app->session->setFlash('error', 'Категория используется в заметках и не может быть удалена.');

            return $this->redirect(['/catalog/index']);
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return ['message' => 'Категория удалена.'];
        }

        Yii::$app->session->setFlash('success', 'Категория удалена.');

        return $this->redirect(['/catalog/index']);
    }

    private function findModel(int $id): Catalog
    {
        $model = Catalog::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException('Запись не найдена.');
        }

        return $model;
    }
}

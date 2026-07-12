<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Catalog;
use common\models\forms\CatalogForm;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CatalogController extends BaseWebController
{
    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Категории',
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
        $form->load(Yii::$app->request->post(), '');

        if (!$form->validate()) {
            return $this->render('form', [
                'title' => 'Добавить категорию',
                'model' => $form,
            ]);
        }

        $catalog = new Catalog(['name' => $form->name]);
        $catalog->save(false);
        Yii::$app->session->setFlash('success', 'Информация успешно добавлена');

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

    public function actionUpdate(): Response|string
    {
        $catalog = $this->findModel((int) Yii::$app->request->post('id'));
        $form = new CatalogForm();
        $form->load(Yii::$app->request->post(), '');

        if (!$form->validate()) {
            return $this->render('form', [
                'title' => 'Редактирование категории',
                'model' => $form,
            ]);
        }

        $catalog->name = (string) $form->name;
        $catalog->save(false);
        Yii::$app->session->setFlash('success', 'Данные обновлены');

        return $this->redirect(['/catalog/index']);
    }

    public function actionDestroy(int $id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->findModel($id)->delete();

        return ['message' => 'Данные успешно удалены.'];
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

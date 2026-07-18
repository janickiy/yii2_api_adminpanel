<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use common\models\Catalog;
use common\models\forms\NoteForm;
use common\models\Notes;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class NotesController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR;

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Заметки',
            'dataProvider' => new ActiveDataProvider([
                'query' => Notes::find()
                    ->with(['user', 'category'])
                    ->orderBy(['id' => SORT_DESC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionEdit(int $id): string
    {
        $note = $this->findModel($id);
        $form = new NoteForm([
            'category_id' => (int) $note->category_id,
            'title' => $note->title,
            'content' => $note->content,
        ]);
        $form->id = (int) $note->id;

        return $this->render('form', [
            'title' => 'Редактирование',
            'model' => $form,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function actionUpdate(?int $id = null): Response|string
    {
        $form = new NoteForm();
        $form->load(Yii::$app->request->post());
        if ($id !== null) {
            $form->id = $id;
        }
        $note = $this->findModel((int) $form->id);

        if (!$form->validate()) {
            $form->id = (int) $note->id;

            return $this->render('form', [
                'title' => 'Редактирование',
                'model' => $form,
                'categories' => $this->categoryOptions(),
            ]);
        }

        $note->category_id = (int) $form->category_id;
        $note->title = (string) $form->title;
        $note->content = (string) $form->content;
        if (!$note->save()) {
            $this->copyErrors($note, $form);
            $form->id = (int) $note->id;

            return $this->render('form', [
                'title' => 'Редактирование',
                'model' => $form,
                'categories' => $this->categoryOptions(),
            ]);
        }
        Yii::info(['event' => 'note.updated.admin', 'note_id' => (int) $note->id], 'application.admin');
        Yii::$app->session->setFlash('success', 'Заметка обновлена.');

        return $this->redirect(['/notes/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $this->deleteRecord($this->findModel($id));
        Yii::info(['event' => 'note.deleted.admin', 'note_id' => $id], 'application.admin');

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return ['message' => 'Заметка удалена.'];
        }

        Yii::$app->session->setFlash('success', 'Заметка удалена.');

        return $this->redirect(['/notes/index']);
    }

    private function findModel(int $id): Notes
    {
        $model = Notes::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException('Запись не найдена.');
        }

        return $model;
    }

    private function categoryOptions(): array
    {
        return ArrayHelper::map(
            Catalog::find()->orderBy(['name' => SORT_ASC])->all(),
            'id',
            'name',
        );
    }
}

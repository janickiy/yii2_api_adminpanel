<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use common\models\forms\NoteForm;
use common\models\Notes;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class NotesController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR;

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Заметки',
        ]);
    }

    public function actionEdit(int $id): string
    {
        $note = $this->findModel($id);
        $form = new NoteForm([
            'title' => $note->title,
            'content' => $note->content,
        ]);
        $form->id = (int) $note->id;

        return $this->render('form', [
            'title' => 'Редактирование',
            'model' => $form,
        ]);
    }

    public function actionUpdate(): Response|string
    {
        $note = $this->findModel((int) Yii::$app->request->post('id'));
        $form = new NoteForm();
        $form->load(Yii::$app->request->post(), '');

        if (!$form->validate()) {
            $form->id = (int) $note->id;

            return $this->render('form', [
                'title' => 'Редактирование',
                'model' => $form,
            ]);
        }

        $note->title = (string) $form->title;
        $note->content = (string) $form->content;
        $note->save(false);
        Yii::$app->session->setFlash('success', 'Данные обновлены успешно');

        return $this->redirect(['/notes/index']);
    }

    public function actionDestroy(int $id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->findModel($id)->delete();

        return ['message' => 'Данные успешно удалены.'];
    }

    private function findModel(int $id): Notes
    {
        $model = Notes::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException('Запись не найдена.');
        }

        return $model;
    }
}

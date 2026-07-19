<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\forms\NoteForm;
use backend\services\NoteManagementService;
use common\models\Admin;
use infrastructure\persistence\records\CategoryRecord;
use infrastructure\persistence\records\NoteRecord;
use Yii;
use yii\base\Module;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\Response;

final class NotesController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR;

    public function __construct(
        string $id,
        Module $module,
        private readonly NoteManagementService $notes,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Заметки',
            'dataProvider' => new ActiveDataProvider([
                'query' => NoteRecord::find()
                    ->with(['user', 'category'])
                    ->orderBy(['id' => SORT_DESC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionEdit(int $id): string
    {
        $note = $this->findRecord(NoteRecord::class, $id);
        $form = new NoteForm();
        $form->loadFromNote($note);

        return $this->renderForm($form);
    }

    public function actionUpdate(int $id): Response|string
    {
        $form = new NoteForm();
        $form->load(Yii::$app->request->post());
        $form->id = $id;
        $note = $this->findRecord(NoteRecord::class, (int) $form->id);

        if (!$form->validate()) {
            $form->id = (int) $note->id;

            return $this->renderForm($form);
        }

        if (!$this->notes->update($note, $form)) {
            $form->id = (int) $note->id;

            return $this->renderForm($form);
        }

        Yii::$app->session->setFlash('success', 'Заметка обновлена.');

        return $this->redirect(['/notes/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $note = $this->findRecord(NoteRecord::class, $id);

        return $this->deleteAndRespond(
            function () use ($note): void {
                $this->notes->delete($note);
            },
            'Заметка удалена.',
            ['/notes/index'],
        );
    }

    private function renderForm(NoteForm $model): string
    {
        return $this->render('form', [
            'title' => 'Редактирование',
            'model' => $model,
            'categories' => $this->categoryOptions(),
        ]);
    }

    private function categoryOptions(): array
    {
        return ArrayHelper::map(
            CategoryRecord::find()->orderBy(['name' => SORT_ASC])->all(),
            'id',
            'name',
        );
    }
}

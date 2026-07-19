<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\forms\NoteForm;
use common\entities\Admin;
use common\entities\Note;
use common\repositories\PersistenceException;
use common\services\AdminService;
use common\services\CategoryService;
use common\services\exceptions\CategoryNotFoundException;
use common\services\exceptions\NotFoundException;
use common\services\NoteService;
use Yii;
use yii\base\Module;
use yii\helpers\ArrayHelper;
use yii\web\Response;

final class NotesController extends BaseWebController
{
    protected string $permissions = Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR;

    public function __construct(
        string $id,
        Module $module,
        private readonly NoteService $notes,
        private readonly CategoryService $categories,
        AdminService $access,
        array $config = [],
    ) {
        parent::__construct($id, $module, $access, $config);
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Заметки',
            'dataProvider' => $this->dataProvider($this->notes->query()),
        ]);
    }

    public function actionEdit(int $id): string
    {
        $note = $this->getNote($id);
        $form = new NoteForm();
        $form->loadFromNote($note);

        return $this->renderForm($form);
    }

    public function actionUpdate(int $id): Response|string
    {
        $note = $this->getNote($id);
        $form = new NoteForm();
        $form->load(Yii::$app->request->post());
        $form->id = $id;

        if (!$form->validate()) {
            return $this->renderForm($form);
        }

        try {
            $this->notes->updateRecord($note, $form->toDto());
        } catch (CategoryNotFoundException) {
            $form->addError('category_id', 'Категория не найдена.');

            return $this->renderForm($form);
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось обновить заметку.');
        }

        Yii::$app->session->setFlash('success', 'Заметка обновлена.');

        return $this->redirect(['/notes/index']);
    }

    public function actionDestroy(int $id): Response|array
    {
        $note = $this->getNote($id);

        return $this->deleteAndRespond(
            function () use ($note): void {
                $this->notes->deleteRecord($note);
            },
            'Заметка удалена.',
            ['/notes/index'],
        );
    }

    private function getNote(int $id): Note
    {
        try {
            return $this->notes->find($id);
        } catch (NotFoundException $exception) {
            $this->throwNotFound($exception, 'Заметка не найдена.');
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось получить заметку.');
        }
    }

    private function renderForm(NoteForm $model): string
    {
        return $this->render('form', [
            'title' => 'Редактирование',
            'model' => $model,
            'categories' => $this->categoryOptions(),
        ]);
    }

    /** @return array<int, string> */
    private function categoryOptions(): array
    {
        try {
            return ArrayHelper::map($this->categories->list(), 'id', 'name');
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось получить категории.');
        }
    }
}

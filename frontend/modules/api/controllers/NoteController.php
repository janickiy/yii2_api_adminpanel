<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use common\models\forms\NoteForm;
use common\models\Notes;
use OpenApi\Attributes as OA;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class NoteController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['GET'],
                'show' => ['GET'],
                'store' => ['POST'],
                'update' => ['PUT'],
                'delete' => ['DELETE'],
            ],
        ];
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'user' => Yii::$app->get('apiUser'),
        ];

        return $behaviors;
    }

    #[OA\Get(
        path: '/api/v1/notes',
        operationId: 'notesIndex',
        summary: 'Список заметок',
        description: 'Возвращает все заметки текущего авторизованного пользователя.',
        security: [['bearerAuth' => []]],
        tags: ['Notes'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список заметок',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Note'),
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
        ],
    )]
    public function actionIndex(): array
    {
        return Notes::find()
            ->where(['user_id' => $this->apiUserId()])
            ->orderBy(['id' => SORT_DESC])
            ->all();
    }

    #[OA\Get(
        path: '/api/v1/notes/{id}',
        operationId: 'notesShow',
        summary: 'Просмотр заметки',
        description: 'Возвращает заметку текущего пользователя по ID.',
        security: [['bearerAuth' => []]],
        tags: ['Notes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID заметки',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Заметка найдена',
                content: new OA\JsonContent(ref: '#/components/schemas/Note'),
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Заметка не найдена',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError'),
            ),
        ],
    )]
    public function actionShow(int $id): array|Notes
    {
        $note = $this->findNote($id);

        if ($note === null) {
            Yii::$app->response->statusCode = 404;

            return ['message' => 'Note not found'];
        }

        return $note;
    }

    #[OA\Post(
        path: '/api/v1/notes/store',
        operationId: 'notesStore',
        summary: 'Создание заметки',
        description: 'Создает заметку для текущего авторизованного пользователя.',
        security: [['bearerAuth' => []]],
        tags: ['Notes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/NotePayload'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Заметка создана',
                content: new OA\JsonContent(ref: '#/components/schemas/Note'),
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибка валидации',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function actionStore(): array|Notes
    {
        $form = new NoteForm();
        $form->load($this->bodyParams(), '');

        if (!$form->validate()) {
            return $this->validationResponse($form);
        }

        $note = new Notes([
            'user_id' => $this->apiUserId(),
            'title' => $form->title,
            'content' => $form->content,
        ]);
        $note->save(false);

        Yii::$app->response->statusCode = 201;

        return $note;
    }

    #[OA\Put(
        path: '/api/v1/notes/update/{id}',
        operationId: 'notesUpdate',
        summary: 'Обновление заметки',
        description: 'Обновляет заголовок и содержимое заметки текущего пользователя.',
        security: [['bearerAuth' => []]],
        tags: ['Notes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID заметки',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/NotePayload'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Заметка обновлена',
                content: new OA\JsonContent(ref: '#/components/schemas/Note'),
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Заметка не найдена',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибка валидации',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function actionUpdate(int $id): array|Notes
    {
        $note = $this->findNote($id);

        if ($note === null) {
            Yii::$app->response->statusCode = 404;

            return ['message' => 'Note not found'];
        }

        $form = new NoteForm();
        $form->load($this->bodyParams(), '');

        if (!$form->validate()) {
            return $this->validationResponse($form);
        }

        $note->title = (string) $form->title;
        $note->content = (string) $form->content;
        $note->save(false);

        return $note;
    }

    #[OA\Delete(
        path: '/api/v1/notes/delete/{id}',
        operationId: 'notesDestroy',
        summary: 'Удаление заметки',
        description: 'Удаляет заметку текущего пользователя по ID.',
        security: [['bearerAuth' => []]],
        tags: ['Notes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID заметки',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Заметка удалена',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Note deleted'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
        ],
    )]
    public function actionDelete(int $id): array
    {
        $note = $this->findNote($id);

        if ($note !== null) {
            $note->delete();
        }

        return ['message' => 'Note deleted'];
    }

    private function findNote(int $id): ?Notes
    {
        return Notes::find()
            ->where(['id' => $id, 'user_id' => $this->apiUserId()])
            ->one();
    }
}

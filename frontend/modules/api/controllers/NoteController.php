<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use application\dto\note\CreateNoteDto;
use application\dto\note\NoteQueryDto;
use application\dto\note\UpdateNoteDto;
use application\services\NoteService;
use domain\exceptions\NotFoundException;
use domain\exceptions\PersistenceException;
use frontend\modules\api\presenters\ApiPresenter;
use OpenApi\Attributes as OA;
use Yii;
use yii\base\Module;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

final class NoteController extends BaseApiController
{
    public function __construct(
        string $id,
        Module $module,
        private readonly NoteService $noteService,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['GET'],
                'show' => ['GET'],
                'create' => ['POST'],
                'update' => ['PUT', 'PATCH'],
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
        operationId: 'listNotes',
        security: [['bearerAuth' => []]],
        tags: ['Notes'],
        responses: [new OA\Response(response: 200, description: 'Notes page')],
    )]
    public function actionIndex(): array
    {
        $query = new NoteQueryDto();
        $query->load(Yii::$app->request->get(), '');
        if (!$query->validate()) {
            return $this->validationResponse($query);
        }

        try {
            $page = $this->noteService->list($this->apiUserId(), $query);
        } catch (NotFoundException $exception) {
            return $this->fieldError('category_id', $exception->getMessage());
        }

        return [
            'data' => array_map(ApiPresenter::note(...), $page->items),
            'meta' => [
                'page' => $page->page,
                'per_page' => $page->perPage,
                'total' => $page->total,
                'page_count' => $page->total === 0 ? 0 : (int) ceil($page->total / $page->perPage),
            ],
        ];
    }

    #[OA\Get(
        path: '/api/v1/notes/{id}',
        operationId: 'showNote',
        security: [['bearerAuth' => []]],
        tags: ['Notes'],
        responses: [
            new OA\Response(response: 200, description: 'Note'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function actionShow(int $id): array
    {
        try {
            $note = $this->noteService->get($this->apiUserId(), $id);
        } catch (NotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), 0, $exception);
        }

        return ['data' => ApiPresenter::note($note)];
    }

    #[OA\Post(
        path: '/api/v1/notes',
        operationId: 'createNote',
        security: [['bearerAuth' => []]],
        tags: ['Notes'],
        responses: [
            new OA\Response(response: 201, description: 'Note created'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function actionCreate(): array
    {
        $dto = new CreateNoteDto();
        $dto->load($this->bodyParams(), '');
        if (!$dto->validate()) {
            return $this->validationResponse($dto);
        }

        try {
            $note = $this->noteService->create($this->apiUserId(), $dto);
        } catch (NotFoundException $exception) {
            return $this->fieldError('category_id', $exception->getMessage());
        } catch (PersistenceException $exception) {
            throw new ServerErrorHttpException('Unable to create the note.', 0, $exception);
        }

        Yii::$app->response->statusCode = 201;

        return ['data' => ApiPresenter::note($note)];
    }

    #[OA\Put(
        path: '/api/v1/notes/{id}',
        operationId: 'updateNote',
        security: [['bearerAuth' => []]],
        tags: ['Notes'],
        responses: [
            new OA\Response(response: 200, description: 'Note updated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function actionUpdate(int $id): array
    {
        $dto = new UpdateNoteDto();
        $dto->load($this->bodyParams(), '');
        if (!$dto->validate()) {
            return $this->validationResponse($dto);
        }

        try {
            $note = $this->noteService->update($this->apiUserId(), $id, $dto);
        } catch (NotFoundException $exception) {
            if (str_starts_with($exception->getMessage(), 'Category')) {
                return $this->fieldError('category_id', $exception->getMessage());
            }

            throw new NotFoundHttpException($exception->getMessage(), 0, $exception);
        } catch (PersistenceException $exception) {
            throw new ServerErrorHttpException('Unable to update the note.', 0, $exception);
        }

        return ['data' => ApiPresenter::note($note)];
    }

    #[OA\Delete(
        path: '/api/v1/notes/{id}',
        operationId: 'deleteNote',
        security: [['bearerAuth' => []]],
        tags: ['Notes'],
        responses: [
            new OA\Response(response: 204, description: 'Note deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function actionDelete(int $id): null
    {
        try {
            $this->noteService->delete($this->apiUserId(), $id);
        } catch (NotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), 0, $exception);
        } catch (PersistenceException $exception) {
            throw new ServerErrorHttpException('Unable to delete the note.', 0, $exception);
        }

        Yii::$app->response->statusCode = 204;

        return null;
    }
}

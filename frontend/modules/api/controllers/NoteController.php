<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use common\repositories\PersistenceException;
use common\services\exceptions\CategoryNotFoundException;
use common\services\exceptions\NotFoundException;
use common\services\NoteService;
use frontend\modules\api\components\ApiExceptionMapper;
use frontend\modules\api\components\ApiRequestContext;
use frontend\modules\api\components\ApiResponder;
use frontend\modules\api\components\RequestInputFactory;
use frontend\modules\api\forms\NoteQueryInput;
use frontend\modules\api\forms\NoteWriteInput;
use yii\base\Module;

final class NoteController extends AuthenticatedApiController
{
    private const CATEGORY_FIELD = 'category_id';

    public function __construct(
        string $id,
        Module $module,
        private readonly NoteService $noteService,
        private readonly RequestInputFactory $requests,
        private readonly ApiRequestContext $context,
        private readonly ApiResponder $responder,
        private readonly ApiExceptionMapper $exceptions,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return $this->withBearerAuthentication(parent::behaviors());
    }

    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
            'show' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'delete' => ['DELETE'],
        ];
    }

    public function actionIndex(): array
    {
        $query = $this->requests->fromQuery(NoteQueryInput::class)->toDto();

        try {
            $page = $this->noteService->list($this->context->userId(), $query);
        } catch (CategoryNotFoundException $exception) {
            $this->exceptions->validationField(self::CATEGORY_FIELD, $exception);
        }

        return $this->responder->notePage($page);
    }

    public function actionShow(int $id): array
    {
        try {
            $note = $this->noteService->get($this->context->userId(), $id);
        } catch (NotFoundException $exception) {
            $this->exceptions->notFound($exception);
        }

        return $this->responder->note($note);
    }

    public function actionCreate(): array
    {
        $dto = $this->requests->fromBody(NoteWriteInput::class)->toDto();

        try {
            $note = $this->noteService->create($this->context->userId(), $dto);
        } catch (CategoryNotFoundException $exception) {
            $this->exceptions->validationField(self::CATEGORY_FIELD, $exception);
        } catch (PersistenceException $exception) {
            $this->exceptions->persistence($exception, 'Unable to create the note.');
        }

        return $this->responder->note($note, 201);
    }

    public function actionUpdate(int $id): array
    {
        $dto = $this->requests->fromBody(NoteWriteInput::class)->toDto();

        try {
            $note = $this->noteService->update($this->context->userId(), $id, $dto);
        } catch (CategoryNotFoundException $exception) {
            $this->exceptions->validationField(self::CATEGORY_FIELD, $exception);
        } catch (NotFoundException $exception) {
            $this->exceptions->notFound($exception);
        } catch (PersistenceException $exception) {
            $this->exceptions->persistence($exception, 'Unable to update the note.');
        }

        return $this->responder->note($note);
    }

    public function actionDelete(int $id): null
    {
        try {
            $this->noteService->delete($this->context->userId(), $id);
        } catch (NotFoundException $exception) {
            $this->exceptions->notFound($exception);
        } catch (PersistenceException $exception) {
            $this->exceptions->persistence($exception, 'Unable to delete the note.');
        }

        return $this->responder->noContent();
    }
}

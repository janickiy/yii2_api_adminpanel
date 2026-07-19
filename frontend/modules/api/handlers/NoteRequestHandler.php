<?php

declare(strict_types=1);

namespace frontend\modules\api\handlers;

use application\services\NoteService;
use domain\exceptions\CategoryNotFoundException;
use domain\exceptions\NotFoundException;
use domain\exceptions\PersistenceException;
use frontend\modules\api\http\ApiExceptionMapper;
use frontend\modules\api\http\ApiRequestContext;
use frontend\modules\api\http\ApiResponder;
use frontend\modules\api\http\input\NoteQueryInput;
use frontend\modules\api\http\input\NoteWriteInput;
use frontend\modules\api\http\RequestInputFactory;

final readonly class NoteRequestHandler
{
    private const CATEGORY_FIELD = 'category_id';

    public function __construct(
        private NoteService $noteService,
        private RequestInputFactory $requests,
        private ApiRequestContext $context,
        private ApiResponder $responder,
        private ApiExceptionMapper $exceptions,
    ) {
    }

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{page: int, per_page: int, total: int, page_count: int}
     * }
     */
    public function index(): array
    {
        $query = $this->requests->fromQuery(NoteQueryInput::class)->toDto();

        try {
            $page = $this->noteService->list($this->context->userId(), $query);
        } catch (CategoryNotFoundException $exception) {
            $this->exceptions->validationField(self::CATEGORY_FIELD, $exception);
        }

        return $this->responder->notePage($page);
    }

    /** @return array{data: array<string, mixed>} */
    public function show(int $id): array
    {
        try {
            $note = $this->noteService->get($this->context->userId(), $id);
        } catch (NotFoundException $exception) {
            $this->exceptions->notFound($exception);
        }

        return $this->responder->note($note);
    }

    /** @return array{data: array<string, mixed>} */
    public function create(): array
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

    /** @return array{data: array<string, mixed>} */
    public function update(int $id): array
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

    public function delete(int $id): null
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

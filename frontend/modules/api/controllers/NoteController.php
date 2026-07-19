<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use frontend\modules\api\handlers\NoteRequestHandler;
use yii\base\Module;
use yii\filters\VerbFilter;

final class NoteController extends AuthenticatedApiController
{
    public function __construct(
        string $id,
        Module $module,
        private readonly NoteRequestHandler $handler,
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

        return $this->withBearerAuthentication($behaviors);
    }

    public function actionIndex(): array
    {
        return $this->handler->index();
    }

    public function actionShow(int $id): array
    {
        return $this->handler->show($id);
    }

    public function actionCreate(): array
    {
        return $this->handler->create();
    }

    public function actionUpdate(int $id): array
    {
        return $this->handler->update($id);
    }

    public function actionDelete(int $id): null
    {
        return $this->handler->delete($id);
    }
}

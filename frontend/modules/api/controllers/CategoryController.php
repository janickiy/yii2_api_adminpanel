<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use frontend\modules\api\handlers\CategoryRequestHandler;
use yii\base\Module;
use yii\filters\VerbFilter;

final class CategoryController extends AuthenticatedApiController
{
    public function __construct(
        string $id,
        Module $module,
        private readonly CategoryRequestHandler $handler,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => ['index' => ['GET']],
        ];

        return $this->withBearerAuthentication($behaviors);
    }

    public function actionIndex(): array
    {
        return $this->handler->index();
    }
}

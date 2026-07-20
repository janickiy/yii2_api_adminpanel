<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use common\services\CategoryService;
use frontend\modules\api\components\ApiResponder;
use yii\base\Module;

final class CategoryController extends AuthenticatedApiController
{
    public function __construct(
        string $id,
        Module $module,
        private readonly CategoryService $categoryService,
        private readonly ApiResponder $responder,
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
        return ['index' => ['GET']];
    }

    public function actionIndex(): array
    {
        return $this->responder->categories($this->categoryService->list());
    }
}

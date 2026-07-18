<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use application\services\CategoryService;
use frontend\modules\api\presenters\ApiPresenter;
use OpenApi\Attributes as OA;
use Yii;
use yii\base\Module;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

final class CategoryController extends BaseApiController
{
    public function __construct(
        string $id,
        Module $module,
        private readonly CategoryService $categoryService,
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
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'user' => Yii::$app->get('apiUser'),
        ];

        return $behaviors;
    }

    #[OA\Get(
        path: '/api/v1/categories',
        operationId: 'listCategories',
        security: [['bearerAuth' => []]],
        tags: ['Categories'],
        responses: [new OA\Response(response: 200, description: 'Categories')],
    )]
    public function actionIndex(): array
    {
        return [
            'data' => array_map(ApiPresenter::category(...), $this->categoryService->list()),
        ];
    }
}

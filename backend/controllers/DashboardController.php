<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\services\DashboardMetricsService;
use common\models\Admin;
use yii\base\Module;

final class DashboardController extends BaseWebController
{
    public function __construct(
        string $id,
        Module $module,
        private readonly DashboardMetricsService $metrics,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'Обзор',
            'counts' => $this->metrics->counts($this->admin()->role === Admin::ROLE_ADMIN),
        ]);
    }
}

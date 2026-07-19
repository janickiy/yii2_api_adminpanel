<?php

declare(strict_types=1);

namespace backend\controllers;

use common\entities\Admin;
use common\repositories\PersistenceException;
use common\services\AdminService;
use common\services\DashboardService;
use yii\base\Module;

final class DashboardController extends BaseWebController
{
    public function __construct(
        string $id,
        Module $module,
        private readonly DashboardService $dashboard,
        AdminService $access,
        array $config = [],
    ) {
        parent::__construct($id, $module, $access, $config);
    }

    public function actionIndex(): string
    {
        try {
            $counts = $this->dashboard->counts($this->canAccess(Admin::ROLE_ADMIN));
        } catch (PersistenceException $exception) {
            $this->throwPersistenceError($exception, 'Не удалось получить статистику.');
        }

        return $this->render('index', [
            'title' => 'Обзор',
            'counts' => $counts,
        ]);
    }
}

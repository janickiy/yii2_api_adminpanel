<?php

declare(strict_types=1);

namespace backend\controllers;

class DashboardController extends BaseWebController
{
    public function actionIndex(): string
    {
        return $this->render('index', [
            'title' => 'dashboard',
        ]);
    }
}

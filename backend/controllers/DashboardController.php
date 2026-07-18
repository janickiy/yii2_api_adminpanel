<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use common\models\Catalog;
use common\models\Message;
use common\models\Notes;
use common\models\User;

class DashboardController extends BaseWebController
{
    public function actionIndex(): string
    {
        $isAdmin = $this->admin()->role === Admin::ROLE_ADMIN;

        return $this->render('index', [
            'title' => 'Обзор',
            'counts' => [
                'notes' => (int) Notes::find()->count(),
                'categories' => (int) Catalog::find()->count(),
                'newMessages' => (int) Message::find()->where(['status' => Message::STATUS_NEW])->count(),
                'users' => $isAdmin ? (int) User::find()->count() : null,
                'admins' => $isAdmin ? (int) Admin::find()->count() : null,
            ],
        ]);
    }
}

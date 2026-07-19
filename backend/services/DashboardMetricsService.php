<?php

declare(strict_types=1);

namespace backend\services;

use common\models\Admin;
use common\models\Message;
use infrastructure\persistence\records\CategoryRecord;
use infrastructure\persistence\records\NoteRecord;
use infrastructure\persistence\records\UserRecord;

final class DashboardMetricsService
{
    /**
     * @return array{notes: int, categories: int, newMessages: int, users: int|null, admins: int|null}
     */
    public function counts(bool $includeRestricted): array
    {
        return [
            'notes' => (int) NoteRecord::find()->count(),
            'categories' => (int) CategoryRecord::find()->count(),
            'newMessages' => (int) Message::find()
                ->where(['status' => Message::STATUS_NEW])
                ->count(),
            'users' => $includeRestricted ? (int) UserRecord::find()->count() : null,
            'admins' => $includeRestricted ? (int) Admin::find()->count() : null,
        ];
    }
}

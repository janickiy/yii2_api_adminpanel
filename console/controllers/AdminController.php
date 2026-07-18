<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\Admin;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

final class AdminController extends Controller
{
    /**
     * Creates the first administrator. Prefer ADMIN_PASSWORD to a command-line password.
     */
    public function actionCreate(
        string $login,
        ?string $password = null,
        string $name = 'Administrator',
        string $role = Admin::ROLE_ADMIN,
    ): int {
        $password = $password ?: (string) getenv('ADMIN_PASSWORD');
        $login = trim($login);
        $name = trim($name);

        if ($login === '' || $name === '') {
            $this->stderr("Login and name are required.\n", Console::FG_RED);

            return ExitCode::DATAERR;
        }
        if (strlen($password) < 8) {
            $this->stderr("Set ADMIN_PASSWORD to a password containing at least 8 characters.\n", Console::FG_RED);

            return ExitCode::DATAERR;
        }
        if (!array_key_exists($role, Admin::roleLabels())) {
            $this->stderr("Role must be admin or moderator.\n", Console::FG_RED);

            return ExitCode::DATAERR;
        }
        if (Admin::find()->where(['login' => $login])->exists()) {
            $this->stderr("An administrator with this login already exists.\n", Console::FG_RED);

            return ExitCode::DATAERR;
        }

        $admin = new Admin([
            'login' => $login,
            'name' => $name,
            'role' => $role,
        ]);
        $admin->setPassword($password);

        if (!$admin->save()) {
            $this->stderr('Unable to create administrator: ' . json_encode($admin->getErrors()) . "\n", Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        Yii::info(['event' => 'admin.created.console', 'admin_id' => (int) $admin->id], 'application');
        $this->stdout("Administrator created.\n", Console::FG_GREEN);

        return ExitCode::OK;
    }
}

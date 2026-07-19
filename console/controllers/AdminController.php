<?php

declare(strict_types=1);

namespace console\controllers;

use common\dtos\AdminWriteDto;
use common\entities\Admin;
use common\repositories\PersistenceException;
use common\services\AdminService;
use common\services\exceptions\ConflictException;
use yii\base\Module;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

final class AdminController extends Controller
{
    public function __construct(
        string $id,
        Module $module,
        private readonly AdminService $admins,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

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
        if (!in_array($role, [Admin::ROLE_ADMIN, Admin::ROLE_MODERATOR], true)) {
            $this->stderr("Role must be admin or moderator.\n", Console::FG_RED);

            return ExitCode::DATAERR;
        }

        try {
            $this->admins->create(new AdminWriteDto(
                name: $name,
                login: $login,
                role: $role,
                password: $password,
            ));
        } catch (ConflictException) {
            $this->stderr("An administrator with this login already exists.\n", Console::FG_RED);

            return ExitCode::DATAERR;
        } catch (PersistenceException) {
            $this->stderr("Unable to create administrator.\n", Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Administrator created.\n", Console::FG_GREEN);

        return ExitCode::OK;
    }
}

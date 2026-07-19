<?php

declare(strict_types=1);

namespace backend\forms;

use common\models\Admin;
use Yii;
use yii\base\Model;

final class AdminLoginForm extends Model
{
    public ?string $login = null;
    public ?string $password = null;
    public bool $remember = false;

    private ?Admin $_admin = null;

    public function rules(): array
    {
        return [
            [['login', 'password'], 'required'],
            ['login', 'string'],
            ['password', 'string', 'min' => 6],
            ['remember', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword(string $attribute): void
    {
        $admin = $this->getAdmin();

        if (!$admin || !$admin->validatePassword((string) $this->password)) {
            $this->addError($attribute, 'Неверный логин или пароль.');
        }
    }

    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        return Yii::$app->user->login($this->getAdmin(), $this->remember ? 3600 * 24 * 30 : 0);
    }

    public function getAdmin(): ?Admin
    {
        if ($this->_admin === null && $this->login !== null) {
            $this->_admin = Admin::findOne(['login' => $this->login]);
        }

        return $this->_admin;
    }
}

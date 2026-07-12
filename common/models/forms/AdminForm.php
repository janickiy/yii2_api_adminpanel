<?php

declare(strict_types=1);

namespace common\models\forms;

use common\models\Admin;
use yii\base\Model;

class AdminForm extends Model
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public ?int $id = null;
    public ?string $login = null;
    public ?string $name = null;
    public ?string $role = null;
    public ?string $password = null;
    public ?string $password_again = null;

    public function rules(): array
    {
        return [
            [['login', 'name'], 'required'],
            [['login', 'name'], 'string', 'min' => 3, 'max' => 255],
            ['id', 'integer'],
            ['role', 'required', 'on' => self::SCENARIO_CREATE],
            ['role', 'in', 'range' => array_keys(Admin::roleLabels())],
            ['password', 'required', 'on' => self::SCENARIO_CREATE],
            [['password', 'password_again'], 'string', 'min' => 6],
            ['password_again', 'compare', 'compareAttribute' => 'password'],
            ['login', 'validateLogin'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'login' => 'логин',
            'name' => 'имя',
            'role' => 'роль',
            'password' => 'пароль',
            'password_again' => 'повтор пароля',
        ];
    }

    public function validateLogin(string $attribute): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $query = Admin::find()->where(['login' => $this->login]);

        if ($this->id !== null) {
            $query->andWhere(['<>', 'id', $this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'Этот логин уже занят.');
        }
    }

    public function loadFromAdmin(Admin $admin): void
    {
        $this->id = (int) $admin->id;
        $this->login = $admin->login;
        $this->name = $admin->name;
        $this->role = $admin->role;
    }
}

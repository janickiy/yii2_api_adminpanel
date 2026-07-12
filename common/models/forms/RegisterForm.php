<?php

declare(strict_types=1);

namespace common\models\forms;

use common\models\User;
use yii\base\Model;

class RegisterForm extends Model
{
    public ?string $name = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $confirm_password = null;

    public function rules(): array
    {
        return [
            [['name', 'email', 'password', 'confirm_password'], 'required'],
            ['name', 'string', 'max' => 255],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => User::class],
            [['password', 'confirm_password'], 'string', 'min' => 6],
            ['confirm_password', 'compare', 'compareAttribute' => 'password'],
        ];
    }
}

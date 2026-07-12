<?php

declare(strict_types=1);

namespace common\models\forms;

use common\models\User;
use yii\base\Model;

class LoginForm extends Model
{
    public ?string $email = null;
    public ?string $password = null;

    private ?User $_user = null;

    public function rules(): array
    {
        return [
            [['email', 'password'], 'required'],
            ['email', 'email'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword(string $attribute): void
    {
        $user = $this->getUser();

        if (!$user || !$user->validatePassword((string) $this->password)) {
            $this->addError($attribute, 'Invalid credentials.');
        }
    }

    public function getUser(): ?User
    {
        if ($this->_user === null && $this->email !== null) {
            $this->_user = User::findOne(['email' => $this->email]);
        }

        return $this->_user;
    }
}

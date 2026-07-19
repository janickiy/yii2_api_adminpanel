<?php

declare(strict_types=1);

namespace backend\forms;

use common\dtos\AdminLoginDto;
use yii\base\Model;

final class AdminLoginForm extends Model
{
    public ?string $login = null;
    public ?string $password = null;
    public bool $remember = false;

    public function rules(): array
    {
        return [
            [['login', 'password'], 'required'],
            ['login', 'trim'],
            ['login', 'string'],
            ['password', 'string', 'min' => 6],
            ['remember', 'boolean'],
        ];
    }

    public function toDto(): AdminLoginDto
    {
        return new AdminLoginDto(
            login: (string) $this->login,
            password: (string) $this->password,
            remember: $this->remember,
        );
    }
}

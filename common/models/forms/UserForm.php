<?php

declare(strict_types=1);

namespace common\models\forms;

use common\models\User;
use yii\base\Model;

final class UserForm extends Model
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $password_again = null;

    public function rules(): array
    {
        return [
            [['name', 'email'], 'required'],
            [['name', 'email'], 'trim'],
            ['email', 'filter', 'filter' => static fn (mixed $value): mixed => is_string($value)
                ? strtolower($value)
                : $value],
            ['id', 'integer'],
            ['name', 'string', 'max' => 160],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'validateEmail'],
            ['password', 'required', 'on' => self::SCENARIO_CREATE],
            [['password', 'password_again'], 'string', 'min' => 8, 'max' => 255],
            ['password_again', 'compare', 'compareAttribute' => 'password'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Имя',
            'email' => 'Email',
            'password' => 'Пароль',
            'password_again' => 'Повтор пароля',
        ];
    }

    public function validateEmail(string $attribute): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $query = User::find()->where(['email' => $this->email]);
        if ($this->id !== null) {
            $query->andWhere(['<>', 'id', $this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'Пользователь с таким email уже существует.');
        }
    }

    public function loadFromUser(User $user): void
    {
        $this->id = (int) $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
    }
}

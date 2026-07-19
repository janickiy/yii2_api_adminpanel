<?php

declare(strict_types=1);

namespace backend\forms;

use infrastructure\persistence\records\UserRecord;

final class UserForm extends BackofficeForm
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $password_again = null;

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }

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

        $query = UserRecord::find()->where(['email' => $this->email]);
        if ($this->id !== null) {
            $query->andWhere(['<>', 'id', $this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'Пользователь с таким email уже существует.');
        }
    }

    public function loadFromUser(UserRecord $user): void
    {
        $this->id = (int) $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
    }
}

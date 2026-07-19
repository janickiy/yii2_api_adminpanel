<?php

declare(strict_types=1);

namespace frontend\forms;

use yii\base\Model;

final class FeedbackForm extends Model
{
    public ?string $subject = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $message = null;

    public function rules(): array
    {
        return [
            [['subject', 'email', 'message'], 'required'],
            [['subject', 'email', 'phone', 'message'], 'trim'],
            ['email', 'email'],
            [['subject', 'email'], 'string', 'max' => 255],
            ['phone', 'string', 'max' => 50],
            ['message', 'string', 'max' => 10000],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'subject' => 'Тема',
            'email' => 'Email',
            'phone' => 'Телефон',
            'message' => 'Сообщение',
        ];
    }
}

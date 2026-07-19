<?php

declare(strict_types=1);

namespace frontend\forms;

use common\dtos\MessageCreateDto;
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
            ['email', 'filter', 'filter' => static fn (mixed $value): mixed => is_string($value)
                ? strtolower($value)
                : $value],
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

    public function toDto(): MessageCreateDto
    {
        return new MessageCreateDto(
            subject: (string) $this->subject,
            email: (string) $this->email,
            phone: $this->phone === null || $this->phone === '' ? null : $this->phone,
            message: (string) $this->message,
        );
    }
}

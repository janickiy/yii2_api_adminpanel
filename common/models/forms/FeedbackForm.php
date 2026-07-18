<?php

declare(strict_types=1);

namespace common\models\forms;

use common\models\Message;
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

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $record = new Message([
            'subject' => $this->subject,
            'email' => $this->email,
            'phone' => $this->phone === '' ? null : $this->phone,
            'message' => $this->message,
            'status' => Message::STATUS_NEW,
        ]);

        if (!$record->save()) {
            foreach ($record->getErrors() as $attribute => $errors) {
                foreach ($errors as $error) {
                    $this->addError($attribute, $error);
                }
            }

            return false;
        }

        return true;
    }
}

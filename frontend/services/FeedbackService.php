<?php

declare(strict_types=1);

namespace frontend\services;

use common\models\Message;
use domain\services\EventLoggerInterface;
use frontend\forms\FeedbackForm;

final readonly class FeedbackService
{
    public function __construct(private EventLoggerInterface $logger)
    {
    }

    public function submit(FeedbackForm $form): bool
    {
        if (!$form->validate()) {
            return false;
        }

        $message = new Message([
            'subject' => $form->subject,
            'email' => $form->email,
            'phone' => $form->phone === '' ? null : $form->phone,
            'message' => $form->message,
            'status' => Message::STATUS_NEW,
        ]);

        if (!$message->save()) {
            $this->copyErrors($message, $form);

            return false;
        }

        $this->logger->info('feedback.created', ['message_id' => (int) $message->id]);

        return true;
    }

    private function copyErrors(Message $message, FeedbackForm $form): void
    {
        foreach ($message->getErrors() as $attribute => $errors) {
            foreach ($errors as $error) {
                $form->addError($attribute, $error);
            }
        }
    }
}

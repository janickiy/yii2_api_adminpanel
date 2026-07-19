<?php

declare(strict_types=1);

namespace common\services;

use common\dtos\MessageCreateDto;
use common\entities\Message;
use common\repositories\MessageRepositoryInterface;
use common\services\exceptions\NotFoundException;
use InvalidArgumentException;
use yii\db\ActiveQuery;

final readonly class MessageService
{
    public function __construct(
        private MessageRepositoryInterface $messages,
        private EventLoggerInterface $logger,
    ) {
    }

    public function submit(MessageCreateDto $dto): Message
    {
        $message = $this->messages->save(new Message([
            'subject' => $dto->subject,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'message' => $dto->message,
            'status' => Message::STATUS_NEW,
        ]));
        $this->logger->info('feedback.created', ['message_id' => (int) $message->id]);

        return $message;
    }

    /** @return ActiveQuery<Message> */
    public function query(): ActiveQuery
    {
        return $this->messages->query();
    }

    public function count(): int
    {
        return $this->messages->count();
    }

    public function countNew(): int
    {
        return $this->messages->countNew();
    }

    public function get(int $id): Message
    {
        $message = $this->messages->findById($id);
        if (!$message instanceof Message) {
            throw new NotFoundException('Message not found.');
        }

        return $message;
    }

    public function markAsRead(Message $message): Message
    {
        if ($message->status !== Message::STATUS_NEW) {
            return $message;
        }

        $message = $this->changeStatus($message, Message::STATUS_READ);
        $this->logger->info('message.read', ['message_id' => (int) $message->id]);

        return $message;
    }

    public function changeStatus(Message $message, string $status): Message
    {
        if (!in_array($status, [Message::STATUS_NEW, Message::STATUS_READ], true)) {
            throw new InvalidArgumentException('Unknown message status.');
        }

        $message->status = $status;
        $message = $this->messages->save($message);
        $this->logger->info('message.status_changed', [
            'message_id' => (int) $message->id,
            'status' => $status,
        ]);

        return $message;
    }

    public function delete(Message $message): void
    {
        $messageId = (int) $message->id;
        $this->messages->delete($message);
        $this->logger->info('message.deleted', ['message_id' => $messageId]);
    }
}

<?php

declare(strict_types=1);

namespace backend\services;

use common\models\Message;
use domain\exceptions\PersistenceException;
use domain\services\EventLoggerInterface;
use InvalidArgumentException;
use Throwable;

final readonly class MessageManagementService
{
    public function __construct(
        private EventLoggerInterface $logger,
        private RecordDeleter $recordDeleter,
    ) {
    }

    public function markAsRead(Message $message): void
    {
        if ($message->status !== Message::STATUS_NEW) {
            return;
        }

        $this->saveStatus($message, Message::STATUS_READ);

        $this->logger->info('message.read', ['message_id' => (int) $message->id]);
    }

    public function changeStatus(Message $message, string $status): void
    {
        if (!array_key_exists($status, Message::statusLabels())) {
            throw new InvalidArgumentException('Unknown message status.');
        }

        $this->saveStatus($message, $status);

        $this->logger->info('message.status_changed', [
            'message_id' => (int) $message->id,
            'status' => $status,
        ]);
    }

    public function delete(Message $message): void
    {
        $this->recordDeleter->delete($message);

        $this->logger->info('message.deleted', ['message_id' => (int) $message->id]);
    }

    private function saveStatus(Message $message, string $status): void
    {
        try {
            if ($message->markAs($status)) {
                return;
            }
        } catch (Throwable $exception) {
            $this->logger->warning('message.status_change_failed', [
                'message_id' => (int) $message->id,
                'exception_class' => $exception::class,
            ]);

            throw new PersistenceException('The message status could not be changed.', 0, $exception);
        }

        $this->logger->warning('message.status_change_failed', [
            'message_id' => (int) $message->id,
            'invalid_attributes' => array_keys($message->getErrors()),
        ]);

        throw new PersistenceException('The message status could not be changed.');
    }
}

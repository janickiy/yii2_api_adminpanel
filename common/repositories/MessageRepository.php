<?php

declare(strict_types=1);

namespace common\repositories;

use common\entities\Message;
use Throwable;
use yii\db\ActiveQuery;

final class MessageRepository extends AbstractActiveRecordRepository implements MessageRepositoryInterface
{
    public function findById(int $id): ?Message
    {
        try {
            $message = Message::findOne(['id' => $id]);

            return $message instanceof Message ? $message : null;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to find the message.', $exception);
        }
    }

    public function query(): ActiveQuery
    {
        return Message::find()->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function count(): int
    {
        try {
            return (int) $this->query()->count();
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to count messages.', $exception);
        }
    }

    public function countNew(): int
    {
        try {
            return (int) $this->query()
                ->where(['status' => Message::STATUS_NEW])
                ->count();
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to count new messages.', $exception);
        }
    }

    public function save(Message $message): Message
    {
        return $this->saveRecord($message, 'Unable to save the message.');
    }

    public function delete(Message $message): void
    {
        $this->deleteRecord($message, 'Unable to delete the message.');
    }
}

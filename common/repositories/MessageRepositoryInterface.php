<?php

declare(strict_types=1);

namespace common\repositories;

use common\entities\Message;
use yii\db\ActiveQuery;

interface MessageRepositoryInterface
{
    public function findById(int $id): ?Message;

    /**
     * @return ActiveQuery<Message>
     */
    public function query(): ActiveQuery;

    public function count(): int;

    public function countNew(): int;

    public function save(Message $message): Message;

    public function delete(Message $message): void;
}

<?php

declare(strict_types=1);

namespace common\repositories;

use Throwable;
use yii\db\ActiveRecord;

abstract class AbstractActiveRecordRepository
{
    /**
     * @template T of ActiveRecord
     * @param T $record
     * @return T
     */
    final protected function saveRecord(ActiveRecord $record, string $errorMessage): ActiveRecord
    {
        $transaction = null;

        try {
            $transaction = $record::getDb()->beginTransaction();
            if (!$record->save()) {
                throw PersistenceException::fromModel($errorMessage, $record);
            }

            if (!$record->refresh()) {
                throw new PersistenceException($errorMessage . ' The record could not be reloaded.');
            }

            $transaction->commit();

            return $record;
        } catch (Throwable $exception) {
            if ($transaction !== null && $transaction->isActive) {
                $transaction->rollBack();
            }

            throw PersistenceException::wrap($errorMessage, $exception);
        }
    }

    final protected function deleteRecord(ActiveRecord $record, string $errorMessage): void
    {
        try {
            if ($record->delete() === false) {
                throw new PersistenceException($errorMessage);
            }
        } catch (Throwable $exception) {
            throw PersistenceException::wrap($errorMessage, $exception);
        }
    }
}

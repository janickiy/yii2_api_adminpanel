<?php

declare(strict_types=1);

namespace backend\services;

use domain\exceptions\PersistenceException;
use Throwable;
use yii\db\ActiveRecord;

final class RecordDeleter
{
    public function delete(ActiveRecord $record): void
    {
        try {
            if ($record->delete() !== 1) {
                throw new PersistenceException('The record could not be deleted.');
            }
        } catch (PersistenceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new PersistenceException('The record could not be deleted.', 0, $exception);
        }
    }
}

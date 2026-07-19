<?php

declare(strict_types=1);

namespace common\repositories;

use RuntimeException;
use Throwable;
use yii\base\Model;

final class PersistenceException extends RuntimeException
{
    public static function fromModel(string $message, Model $model): self
    {
        $attributes = array_keys($model->getErrors());

        if ($attributes !== []) {
            $message = sprintf('%s Invalid attributes: %s.', $message, implode(', ', $attributes));
        }

        return new self($message);
    }

    public static function wrap(string $message, Throwable $exception): self
    {
        return $exception instanceof self
            ? $exception
            : new self($message, 0, $exception);
    }
}

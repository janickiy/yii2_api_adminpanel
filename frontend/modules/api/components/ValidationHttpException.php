<?php

declare(strict_types=1);

namespace frontend\modules\api\components;

use yii\web\UnprocessableEntityHttpException;

final class ValidationHttpException extends UnprocessableEntityHttpException
{
    /**
     * @param array<string, list<string>> $fields
     */
    public function __construct(
        private readonly array $fields,
        string $message = 'Validation failed.',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, list<string>>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public static function forField(string $field, string $message): self
    {
        return new self([$field => [$message]]);
    }
}

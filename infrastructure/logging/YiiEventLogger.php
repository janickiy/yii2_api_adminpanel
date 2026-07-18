<?php

declare(strict_types=1);

namespace infrastructure\logging;

use domain\services\EventLoggerInterface;
use Stringable;
use Throwable;
use Yii;

final class YiiEventLogger implements EventLoggerInterface
{
    private const CATEGORY = 'application.events';
    private const REDACTED = '[redacted]';
    private const MAX_STRING_LENGTH = 1000;

    public function info(string $message, array $context = []): void
    {
        Yii::info($this->payload($message, $context), self::CATEGORY);
    }

    public function warning(string $message, array $context = []): void
    {
        Yii::warning($this->payload($message, $context), self::CATEGORY);
    }

    /**
     * @param array<string, mixed> $context
     * @return array{event: string, context: array<string, mixed>}
     */
    private function payload(string $message, array $context): array
    {
        return [
            'event' => $message,
            'context' => $this->sanitizeArray($context),
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $name = (string) $key;
            $sanitized[$name] = $this->isSensitiveKey($name)
                ? self::REDACTED
                : $this->sanitizeValue($value);
        }

        return $sanitized;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            /** @var array<string, mixed> $value */
            return $this->sanitizeArray($value);
        }

        if ($value instanceof Throwable) {
            return ['exception' => $value::class];
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_string($value)) {
            if ($this->looksLikeJwt($value)) {
                return self::REDACTED;
            }

            return mb_strlen($value) > self::MAX_STRING_LENGTH
                ? mb_substr($value, 0, self::MAX_STRING_LENGTH) . '…'
                : $value;
        }

        if (is_object($value)) {
            return ['type' => $value::class];
        }

        if (is_resource($value)) {
            return ['type' => get_resource_type($value)];
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match(
            '/(?:password|passwd|secret|token|authorization|cookie|jwt|content|body|payload)/i',
            $key,
        ) === 1;
    }

    private function looksLikeJwt(string $value): bool
    {
        return preg_match('/\A[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\z/D', $value) === 1;
    }
}

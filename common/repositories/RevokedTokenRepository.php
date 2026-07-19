<?php

declare(strict_types=1);

namespace common\repositories;

use common\entities\RevokedToken;
use common\services\EventLoggerInterface;
use Throwable;
use yii\db\Expression;

final class RevokedTokenRepository implements RevokedTokenRepositoryInterface
{
    public function __construct(private readonly EventLoggerInterface $logger)
    {
    }

    /** @phpstan-impure */
    public function isRevoked(string $jti): bool
    {
        try {
            return RevokedToken::find()
                ->where(['jti' => $jti])
                ->andWhere(['>', 'expires_at', new Expression('CURRENT_TIMESTAMP')])
                ->exists();
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to verify access token revocation.', $exception);
        }
    }

    public function revoke(string $jti, int $expiresAt): void
    {
        try {
            RevokedToken::getDb()->createCommand()->upsert(
                RevokedToken::tableName(),
                [
                    'jti' => $jti,
                    'expires_at' => new Expression(
                        'TO_TIMESTAMP(:jwtExpiresAt)',
                        [':jwtExpiresAt' => $expiresAt],
                    ),
                    'created_at' => new Expression('CURRENT_TIMESTAMP'),
                ],
                false,
            )->execute();
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to revoke the access token.', $exception);
        }

        try {
            $this->deleteExpired();
        } catch (PersistenceException $exception) {
            $this->logger->warning('auth.revoked_tokens_cleanup_failed', [
                'exception_class' => $exception::class,
            ]);
        }
    }

    public function deleteExpired(): int
    {
        try {
            return RevokedToken::deleteAll([
                '<=',
                'expires_at',
                new Expression('CURRENT_TIMESTAMP'),
            ]);
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to delete expired access tokens.', $exception);
        }
    }
}

<?php

declare(strict_types=1);

namespace common\models;

use domain\exceptions\PersistenceException;
use infrastructure\persistence\records\RevokedTokenRecord;
use Throwable;
use yii\db\Expression;
use yii\db\IntegrityException;

/**
 * Backward-compatible alias for legacy callers.
 *
 * @property int $id
 * @property string $jti
 * @property string $expires_at
 * @property string $created_at
 */
class RevokedToken extends RevokedTokenRecord
{
    public static function isRevoked(string $jti): bool
    {
        return static::find()
            ->where(['jti' => $jti])
            ->andWhere(['>', 'expires_at', new Expression('CURRENT_TIMESTAMP')])
            ->exists();
    }

    public static function revoke(string $jti, int $expiresAt): void
    {
        try {
            if (self::hasRevocation($jti)) {
                return;
            }

            // Late-static construction is intentional for legacy subclasses.
            // @phpstan-ignore new.static
            $record = new static([
                'jti' => $jti,
                'expires_at' => new Expression(
                    'TO_TIMESTAMP(:jwtExpiresAt)',
                    [':jwtExpiresAt' => $expiresAt],
                ),
            ]);

            if (!$record->save()) {
                throw new PersistenceException('Unable to revoke the access token.');
            }
        } catch (IntegrityException $exception) {
            if (self::hasRevocation($jti)) {
                return;
            }

            throw new PersistenceException('Unable to revoke the access token.', 0, $exception);
        } catch (PersistenceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new PersistenceException('Unable to revoke the access token.', 0, $exception);
        }
    }

    /** @phpstan-impure */
    private static function hasRevocation(string $jti): bool
    {
        return static::find()->where(['jti' => $jti])->exists();
    }
}

<?php

declare(strict_types=1);

namespace infrastructure\security;

use domain\entities\User;
use domain\exceptions\AuthenticationException;
use domain\exceptions\PersistenceException;
use domain\services\TokenManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use infrastructure\persistence\records\RevokedTokenRecord;
use InvalidArgumentException;
use Throwable;
use yii\db\Expression;
use yii\db\IntegrityException;

final readonly class FirebaseJwtTokenManager implements TokenManagerInterface
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private string $secret,
        private string $issuer,
        private string $audience,
        private int $ttl = 3600,
        private int $leeway = 10,
    ) {
        if (strlen($this->secret) < 32) {
            throw new InvalidArgumentException('JWT secret must contain at least 32 bytes.');
        }
        if ($this->issuer === '' || $this->audience === '') {
            throw new InvalidArgumentException('JWT issuer and audience must not be empty.');
        }
        if ($this->ttl < 1 || $this->leeway < 0) {
            throw new InvalidArgumentException('JWT TTL must be positive and leeway must not be negative.');
        }
    }

    public function issue(User $user): string
    {
        $userId = $user->getId();
        if ($userId === null || $userId < 1) {
            throw new InvalidArgumentException('A persisted user is required to issue an access token.');
        }

        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttl,
            'sub' => (string) $userId,
            'jti' => bin2hex(random_bytes(16)),
        ];

        return JWT::encode($payload, $this->secret, self::ALGORITHM);
    }

    public function validateAndGetUserId(string $token): int
    {
        $claims = $this->decodeAndValidate($token);
        $this->assertNotRevoked($claims['jti']);

        return $claims['userId'];
    }

    public function revoke(string $token): void
    {
        $claims = $this->decodeAndValidate($token);

        try {
            if ($this->isRevoked($claims['jti'])) {
                return;
            }

            $record = new RevokedTokenRecord([
                'jti' => $claims['jti'],
                'expires_at' => new Expression(
                    'TO_TIMESTAMP(:jwtExpiresAt)',
                    [':jwtExpiresAt' => $claims['expiresAt'] + $this->leeway],
                ),
            ]);

            if (!$record->save()) {
                $attributes = array_keys($record->getErrors());
                $suffix = $attributes === [] ? '' : ' Invalid attributes: ' . implode(', ', $attributes) . '.';

                throw new PersistenceException('Unable to revoke the access token.' . $suffix);
            }
        } catch (IntegrityException $exception) {
            if ($this->isRevoked($claims['jti'])) {
                return;
            }

            throw new PersistenceException('Unable to revoke the access token.', 0, $exception);
        } catch (PersistenceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new PersistenceException('Unable to revoke the access token.', 0, $exception);
        }
    }

    /**
     * @return array{userId: int, jti: string, expiresAt: int}
     */
    private function decodeAndValidate(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            throw new AuthenticationException('Access token is required.');
        }

        $previousLeeway = JWT::$leeway;
        JWT::$leeway = $this->leeway;

        try {
            $payload = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (Throwable $exception) {
            throw new AuthenticationException('Invalid or expired access token.', 0, $exception);
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        $issuer = is_string($payload->iss ?? null) ? $payload->iss : '';
        if ($issuer === '' || !hash_equals($this->issuer, $issuer)) {
            throw new AuthenticationException('Invalid access token issuer.');
        }

        if (!$this->matchesAudience($payload->aud ?? null)) {
            throw new AuthenticationException('Invalid access token audience.');
        }

        foreach (['iat', 'nbf', 'exp'] as $claim) {
            if (!isset($payload->{$claim}) || !is_int($payload->{$claim})) {
                throw new AuthenticationException(sprintf('Access token claim "%s" is missing or invalid.', $claim));
            }
        }

        if ($payload->exp <= $payload->iat || $payload->nbf < $payload->iat) {
            throw new AuthenticationException('Access token time claims are invalid.');
        }

        $subject = is_string($payload->sub ?? null) ? $payload->sub : '';
        if ($subject === '' || !ctype_digit($subject) || (int) $subject < 1) {
            throw new AuthenticationException('Access token subject is invalid.');
        }

        $jti = is_string($payload->jti ?? null) ? $payload->jti : '';
        if (preg_match('/\A[a-f0-9]{32}\z/D', $jti) !== 1) {
            throw new AuthenticationException('Access token identifier is invalid.');
        }

        return [
            'userId' => (int) $subject,
            'jti' => $jti,
            'expiresAt' => $payload->exp,
        ];
    }

    private function matchesAudience(mixed $claim): bool
    {
        if (is_string($claim)) {
            return hash_equals($this->audience, $claim);
        }

        if (!is_array($claim)) {
            return false;
        }

        foreach ($claim as $audience) {
            if (is_string($audience) && hash_equals($this->audience, $audience)) {
                return true;
            }
        }

        return false;
    }

    private function assertNotRevoked(string $jti): void
    {
        try {
            if ($this->isRevoked($jti)) {
                throw new AuthenticationException('Access token has been revoked.');
            }
        } catch (AuthenticationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new PersistenceException('Unable to verify access token revocation.', 0, $exception);
        }
    }

    /** @phpstan-impure */
    private function isRevoked(string $jti): bool
    {
        return RevokedTokenRecord::find()
            ->where(['jti' => $jti])
            ->andWhere(['>', 'expires_at', new Expression('CURRENT_TIMESTAMP')])
            ->exists();
    }
}

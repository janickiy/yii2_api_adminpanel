<?php

declare(strict_types=1);

namespace common\services;

use common\entities\User;
use common\repositories\RevokedTokenRepositoryInterface;
use common\services\exceptions\AuthenticationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use Throwable;

final readonly class JwtTokenManager implements TokenManagerInterface
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private RevokedTokenRepositoryInterface $revokedTokens,
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
            throw new InvalidArgumentException(
                'JWT TTL must be positive and leeway must not be negative.',
            );
        }
    }

    public function issue(User $user): string
    {
        $userId = (int) $user->id;
        if ($userId < 1) {
            throw new InvalidArgumentException('A persisted user is required to issue an access token.');
        }

        $now = time();

        return JWT::encode([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttl,
            'sub' => (string) $userId,
            'jti' => bin2hex(random_bytes(16)),
        ], $this->secret, self::ALGORITHM);
    }

    public function validateAndGetUserId(string $token): int
    {
        $claims = $this->decodeAndValidate($token);
        if ($this->revokedTokens->isRevoked($claims['jti'])) {
            throw new AuthenticationException('Access token has been revoked.');
        }

        return $claims['userId'];
    }

    public function revoke(string $token): void
    {
        $claims = $this->decodeAndValidate($token);
        $this->revokedTokens->revoke($claims['jti'], $claims['expiresAt'] + $this->leeway);
    }

    /** @return array{userId: int, jti: string, expiresAt: int} */
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
                throw new AuthenticationException(sprintf(
                    'Access token claim "%s" is missing or invalid.',
                    $claim,
                ));
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
}

<?php

declare(strict_types=1);

namespace common\models;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string $created_at
 * @property string $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return '{{%users}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['name', 'email', 'password'], 'required'],
            [['name', 'email', 'password', 'remember_token'], 'string', 'max' => 255],
            ['email', 'email'],
            ['email', 'unique'],
            [['email_verified_at', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function fields(): array
    {
        return ['id', 'name', 'email', 'created_at', 'updated_at'];
    }

    public static function findIdentity($id): ?self
    {
        return self::findOne((int) $id);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        try {
            $payload = self::decodeAccessToken((string) $token);
        } catch (ExpiredException) {
            return null;
        } catch (Throwable) {
            return null;
        }

        $jti = (string) ($payload['jti'] ?? '');
        if ($jti === '' || RevokedToken::isRevoked($jti)) {
            return null;
        }

        $userId = (int) ($payload['sub'] ?? 0);

        return $userId > 0 ? self::findIdentity($userId) : null;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getAuthKey(): ?string
    {
        return null;
    }

    public function validateAuthKey($authKey): bool
    {
        return false;
    }

    public function getNotes(): ActiveQuery
    {
        return $this->hasMany(Notes::class, ['user_id' => 'id']);
    }

    public function setPassword(string $password): void
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    public function generateAccessToken(): string
    {
        $now = time();
        $ttl = (int) Yii::$app->params['jwtTtl'];

        $payload = [
            'iss' => Yii::$app->params['jwtIssuer'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'sub' => (string) $this->id,
            'jti' => bin2hex(random_bytes(16)),
        ];

        return JWT::encode($payload, self::jwtSecret(), 'HS256');
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeAccessToken(string $token): array
    {
        return (array) JWT::decode($token, new Key(self::jwtSecret(), 'HS256'));
    }

    public static function revokeAccessToken(string $token): void
    {
        $payload = self::decodeAccessToken($token);
        $jti = (string) ($payload['jti'] ?? '');

        if ($jti === '') {
            return;
        }

        RevokedToken::revoke($jti, (int) ($payload['exp'] ?? time()));
    }

    private static function jwtSecret(): string
    {
        return (string) Yii::$app->params['jwtSecret'];
    }
}

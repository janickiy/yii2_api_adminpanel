<?php

declare(strict_types=1);

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $login
 * @property string $password
 * @property string|null $name
 * @property string $role
 * @property string|null $auth_key
 * @property string $created_at
 * @property string $updated_at
 */
class Admin extends ActiveRecord implements IdentityInterface
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MODERATOR = 'moderator';

    public static function tableName(): string
    {
        return '{{%admins}}';
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
            [['login', 'password', 'role'], 'required'],
            ['login', 'string', 'max' => 120],
            ['name', 'string', 'max' => 160],
            ['password', 'string', 'max' => 255],
            ['role', 'string', 'max' => 20],
            ['auth_key', 'string', 'max' => 64],
            ['login', 'unique'],
            ['role', 'in', 'range' => array_keys(self::roleLabels())],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function fields(): array
    {
        return ['id', 'login', 'name', 'role', 'created_at', 'updated_at'];
    }

    public static function roleLabels(): array
    {
        return [
            self::ROLE_ADMIN => 'Администратор',
            self::ROLE_MODERATOR => 'Модератор',
        ];
    }

    public static function findIdentity($id): ?self
    {
        return self::findOne((int) $id);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        return null;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getAuthKey(): ?string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return hash_equals((string) $this->auth_key, (string) $authKey);
    }

    public function setPassword(string $password): void
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    public function canAccess(string $permissions): bool
    {
        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }

        return in_array($this->role, explode('|', $permissions), true);
    }

    public function beforeSave($insert): bool
    {
        if ($this->auth_key === null || $this->auth_key === '') {
            $this->auth_key = Yii::$app->security->generateRandomString(64);
        }

        return parent::beforeSave($insert);
    }
}

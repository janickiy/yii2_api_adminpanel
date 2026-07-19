<?php

declare(strict_types=1);

namespace common\entities;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $login
 * @property string $password
 * @property string $name
 * @property string $role
 * @property string $auth_key
 * @property string $created_at
 * @property string $updated_at
 */
class Admin extends ActiveRecord
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
            [['login', 'password', 'name', 'role', 'auth_key'], 'required'],
            ['login', 'string', 'max' => 120],
            ['name', 'string', 'max' => 160],
            ['password', 'string', 'max' => 255],
            ['role', 'string', 'max' => 20],
            ['auth_key', 'string', 'max' => 64],
            ['login', 'unique'],
            ['role', 'in', 'range' => [self::ROLE_ADMIN, self::ROLE_MODERATOR]],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function fields(): array
    {
        return ['id', 'login', 'name', 'role', 'created_at', 'updated_at'];
    }
}

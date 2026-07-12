<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $jti
 * @property string $expires_at
 * @property string $created_at
 */
class RevokedToken extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%revoked_tokens}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['jti', 'expires_at'], 'required'],
            ['jti', 'string', 'max' => 64],
            ['jti', 'unique'],
            ['expires_at', 'safe'],
        ];
    }

    public static function isRevoked(string $jti): bool
    {
        return self::find()
            ->where(['jti' => $jti])
            ->andWhere(['>=', 'expires_at', date('Y-m-d H:i:s')])
            ->exists();
    }

    public static function revoke(string $jti, int $expiresAt): void
    {
        if (self::find()->where(['jti' => $jti])->exists()) {
            return;
        }

        $token = new self([
            'jti' => $jti,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
        ]);
        $token->save(false);
    }
}

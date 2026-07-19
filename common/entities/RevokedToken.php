<?php

declare(strict_types=1);

namespace common\entities;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $jti
 * @property string $expires_at
 * @property string $created_at
 */
final class RevokedToken extends ActiveRecord
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
                'value' => new Expression('CURRENT_TIMESTAMP'),
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

    public function fields(): array
    {
        return ['id', 'jti', 'expires_at', 'created_at'];
    }
}

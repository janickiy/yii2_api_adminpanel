<?php

declare(strict_types=1);

namespace common\entities;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $subject
 * @property string $email
 * @property string|null $phone
 * @property string $message
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
final class Message extends ActiveRecord
{
    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';

    public static function tableName(): string
    {
        return '{{%messages}}';
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
            [['subject', 'email', 'message'], 'required'],
            ['message', 'string'],
            [['subject', 'email'], 'string', 'max' => 255],
            ['phone', 'string', 'max' => 50],
            ['email', 'email'],
            ['status', 'default', 'value' => self::STATUS_NEW],
            ['status', 'in', 'range' => [self::STATUS_NEW, self::STATUS_READ]],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function fields(): array
    {
        return [
            'id',
            'subject',
            'email',
            'phone',
            'message',
            'status',
            'created_at',
            'updated_at',
        ];
    }
}

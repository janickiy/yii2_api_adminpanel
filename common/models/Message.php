<?php

declare(strict_types=1);

namespace common\models;

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
            [['message'], 'string'],
            [['subject', 'email'], 'string', 'max' => 255],
            ['phone', 'string', 'max' => 50],
            ['email', 'email'],
            ['status', 'default', 'value' => self::STATUS_NEW],
            ['status', 'in', 'range' => array_keys(self::statusLabels())],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_NEW => 'Новое',
            self::STATUS_READ => 'Просмотрено',
        ];
    }

    public function markAs(string $status): bool
    {
        if (!array_key_exists($status, self::statusLabels())) {
            return false;
        }

        $this->status = $status;

        return $this->save(true, ['status', 'updated_at']);
    }
}

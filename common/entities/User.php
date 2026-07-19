<?php

declare(strict_types=1);

namespace common\entities;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $created_at
 * @property string $updated_at
 */
class User extends ActiveRecord
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
                'value' => new Expression('CURRENT_TIMESTAMP'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['name', 'email', 'password'], 'required'],
            ['name', 'string', 'max' => 160],
            [['email', 'password'], 'string', 'max' => 255],
            ['email', 'email'],
            ['email', 'unique'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function fields(): array
    {
        return ['id', 'name', 'email', 'created_at', 'updated_at'];
    }

    /**
     * @return ActiveQuery<Note>
     */
    public function getNotes(): ActiveQuery
    {
        return $this->hasMany(Note::class, ['user_id' => 'id']);
    }
}

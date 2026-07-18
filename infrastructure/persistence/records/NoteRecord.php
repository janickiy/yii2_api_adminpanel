<?php

declare(strict_types=1);

namespace infrastructure\persistence\records;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property string $title
 * @property string $content
 * @property string $created_at
 * @property string $updated_at
 */
class NoteRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%notes}}';
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
            [['user_id', 'category_id', 'title', 'content'], 'required'],
            [['user_id', 'category_id'], 'integer'],
            ['title', 'string', 'max' => 255],
            ['content', 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function fields(): array
    {
        return ['id', 'user_id', 'category_id', 'title', 'content', 'created_at', 'updated_at'];
    }

    /**
     * @return ActiveQuery<UserRecord>
     */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(UserRecord::class, ['id' => 'user_id']);
    }

    /**
     * @return ActiveQuery<CategoryRecord>
     */
    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(CategoryRecord::class, ['id' => 'category_id']);
    }
}

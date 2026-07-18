<?php

declare(strict_types=1);

namespace infrastructure\persistence\records;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 */
class CategoryRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%categories}}';
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
            ['name', 'required'],
            ['name', 'string', 'max' => 120],
            ['name', 'unique'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function fields(): array
    {
        return ['id', 'name', 'created_at', 'updated_at'];
    }

    /**
     * @return ActiveQuery<NoteRecord>
     */
    public function getNotes(): ActiveQuery
    {
        return $this->hasMany(NoteRecord::class, ['category_id' => 'id']);
    }
}

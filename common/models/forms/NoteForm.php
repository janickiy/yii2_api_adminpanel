<?php

declare(strict_types=1);

namespace common\models\forms;

use common\models\Catalog;
use yii\base\Model;

class NoteForm extends Model
{
    public ?int $id = null;
    public ?int $category_id = null;
    public ?string $title = null;
    public ?string $content = null;

    public function rules(): array
    {
        return [
            [['id', 'category_id'], 'integer'],
            [['category_id', 'title', 'content'], 'required'],
            ['category_id', 'exist', 'targetClass' => Catalog::class, 'targetAttribute' => 'id'],
            ['title', 'string', 'max' => 255],
            ['content', 'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'category_id' => 'Категория',
            'title' => 'Название',
            'content' => 'Заметка',
        ];
    }
}

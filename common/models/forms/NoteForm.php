<?php

declare(strict_types=1);

namespace common\models\forms;

use yii\base\Model;

class NoteForm extends Model
{
    public ?int $id = null;
    public ?string $title = null;
    public ?string $content = null;

    public function rules(): array
    {
        return [
            ['id', 'integer'],
            [['title', 'content'], 'required'],
            ['title', 'string', 'max' => 255],
            ['content', 'string'],
        ];
    }
}

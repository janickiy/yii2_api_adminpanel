<?php

declare(strict_types=1);

namespace common\models\forms;

use yii\base\Model;

class CatalogForm extends Model
{
    public ?int $id = null;
    public ?string $name = null;

    public function rules(): array
    {
        return [
            ['id', 'integer'],
            ['name', 'required'],
            ['name', 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'имя',
        ];
    }
}

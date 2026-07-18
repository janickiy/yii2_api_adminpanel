<?php

declare(strict_types=1);

namespace common\models\forms;

use common\models\Catalog;
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
            ['name', 'trim'],
            ['name', 'string', 'max' => 120],
            ['name', 'validateName'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'имя',
        ];
    }

    public function validateName(string $attribute): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $query = Catalog::find()->where(['name' => $this->name]);
        if ($this->id !== null) {
            $query->andWhere(['<>', 'id', $this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'Категория с таким названием уже существует.');
        }
    }
}

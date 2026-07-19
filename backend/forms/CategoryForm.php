<?php

declare(strict_types=1);

namespace backend\forms;

use infrastructure\persistence\records\CategoryRecord;

final class CategoryForm extends BackofficeForm
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

        $query = CategoryRecord::find()->where(['name' => $this->name]);
        if ($this->id !== null) {
            $query->andWhere(['<>', 'id', $this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'Категория с таким названием уже существует.');
        }
    }

    public function loadFromCategory(CategoryRecord $category): void
    {
        $this->id = (int) $category->id;
        $this->name = $category->name;
    }
}

<?php

declare(strict_types=1);

namespace backend\forms;

use common\dtos\CategoryWriteDto;
use common\entities\Category;

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
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'имя',
        ];
    }

    public function loadFromCategory(Category $category): void
    {
        $this->id = (int) $category->id;
        $this->name = $category->name;
    }

    public function toDto(): CategoryWriteDto
    {
        return new CategoryWriteDto((string) $this->name);
    }
}

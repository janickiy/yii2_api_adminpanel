<?php

declare(strict_types=1);

namespace backend\forms;

use infrastructure\persistence\records\CategoryRecord;
use infrastructure\persistence\records\NoteRecord;

final class NoteForm extends BackofficeForm
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
            ['category_id', 'exist', 'targetClass' => CategoryRecord::class, 'targetAttribute' => 'id'],
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

    public function loadFromNote(NoteRecord $note): void
    {
        $this->id = (int) $note->id;
        $this->category_id = (int) $note->category_id;
        $this->title = $note->title;
        $this->content = $note->content;
    }
}

<?php

declare(strict_types=1);

namespace backend\forms;

use common\dtos\NoteWriteDto;
use common\entities\Note;

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
            [['title', 'content'], 'trim'],
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

    public function loadFromNote(Note $note): void
    {
        $this->id = (int) $note->id;
        $this->category_id = (int) $note->category_id;
        $this->title = $note->title;
        $this->content = $note->content;
    }

    public function toDto(): NoteWriteDto
    {
        return new NoteWriteDto(
            categoryId: (int) $this->category_id,
            title: (string) $this->title,
            content: (string) $this->content,
        );
    }
}

<?php

declare(strict_types=1);

use yii\db\Migration;

class m260704_000002_create_notes_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%notes}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string()->notNull(),
            'content' => $this->text()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_notes_user_id',
            '{{%notes}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );
        $this->createIndex('idx_notes_user_id', '{{%notes}}', 'user_id');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_notes_user_id', '{{%notes}}');
        $this->dropTable('{{%notes}}');
    }
}

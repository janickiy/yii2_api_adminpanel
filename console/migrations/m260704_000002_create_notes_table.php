<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260704_000002_create_notes_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%notes}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'content' => $this->text()->notNull(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx_notes_user_id', '{{%notes}}', 'user_id');
        $this->addForeignKey('fk_notes_user_id', '{{%notes}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_notes_user_id', '{{%notes}}');
        $this->dropTable('{{%notes}}');
    }
}

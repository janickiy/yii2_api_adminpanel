<?php

declare(strict_types=1);

use yii\db\Migration;

class m260704_000003_create_revoked_tokens_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%revoked_tokens}}', [
            'id' => $this->primaryKey(),
            'jti' => $this->string(64)->notNull()->unique(),
            'expires_at' => $this->dateTime()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx_revoked_tokens_expires_at', '{{%revoked_tokens}}', 'expires_at');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%revoked_tokens}}');
    }
}

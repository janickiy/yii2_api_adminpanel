<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260712_000001_create_admin_and_catalog_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%admin}}', [
            'id' => $this->primaryKey(),
            'login' => $this->string(120)->notNull()->unique(),
            'password' => $this->string(255)->notNull(),
            'name' => $this->string(160),
            'role' => $this->string(20)->notNull(),
            'remember_token' => $this->string(100),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createTable('{{%catalog}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(120)->notNull(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%catalog}}');
        $this->dropTable('{{%admin}}');
    }
}

<?php

declare(strict_types=1);

use yii\db\Migration;

class m260704_000001_create_users_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'email' => $this->string()->notNull()->unique(),
            'email_verified_at' => $this->dateTime()->null(),
            'password' => $this->string()->notNull(),
            'remember_token' => $this->string(100)->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createTable('{{%password_reset_tokens}}', [
            'email' => $this->string()->notNull(),
            'token' => $this->string()->notNull(),
            'created_at' => $this->dateTime()->null(),
        ]);
        $this->addPrimaryKey('pk_password_reset_tokens_email', '{{%password_reset_tokens}}', 'email');

        $this->createTable('{{%sessions}}', [
            'id' => $this->string()->notNull(),
            'user_id' => $this->integer()->null(),
            'ip_address' => $this->string(45)->null(),
            'user_agent' => $this->text()->null(),
            'payload' => $this->text()->notNull(),
            'last_activity' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('pk_sessions_id', '{{%sessions}}', 'id');
        $this->createIndex('idx_sessions_user_id', '{{%sessions}}', 'user_id');
        $this->createIndex('idx_sessions_last_activity', '{{%sessions}}', 'last_activity');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%sessions}}');
        $this->dropTable('{{%password_reset_tokens}}');
        $this->dropTable('{{%users}}');
    }
}

<?php

declare(strict_types=1);

use yii\db\Migration;

class m260712_000001_create_admin_and_catalog_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%admin}}', [
            'id' => $this->primaryKey(),
            'login' => $this->string()->notNull()->unique(),
            'password' => $this->string()->notNull(),
            'name' => $this->string()->null(),
            'role' => $this->string()->notNull(),
            'remember_token' => $this->string(100)->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $now = date('Y-m-d H:i:s');
        $this->insert('{{%admin}}', [
            'login' => 'admin',
            'password' => Yii::$app->security->generatePasswordHash('1234567'),
            'name' => 'Админ',
            'role' => 'admin',
            'remember_token' => Yii::$app->security->generateRandomString(64),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->createTable('{{%catalog}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%catalog}}');
        $this->dropTable('{{%admin}}');
    }
}

<?php

declare(strict_types=1);

use yii\db\Expression;
use yii\db\Migration;
use yii\db\Query;

final class m260719_000001_upgrade_notes_service extends Migration
{
    public function safeUp(): void
    {
        $this->upgradeUsers();
        $this->upgradeCategories();
        $this->upgradeNotes();
        $this->upgradeAdmins();
        $this->createMessages();
    }

    public function safeDown(): bool
    {
        echo "m260719_000001_upgrade_notes_service is irreversible; restore a database backup to roll it back.\n";

        return false;
    }

    private function upgradeUsers(): void
    {
        if (!$this->tableExists('users')) {
            return;
        }

        $table = $this->db->quoteTableName($this->tableName('users'));
        $duplicate = $this->db->createCommand(
            "SELECT LOWER(email) FROM {$table} GROUP BY LOWER(email) HAVING COUNT(*) > 1 LIMIT 1",
        )->queryScalar();
        if ($duplicate !== false) {
            throw new RuntimeException(sprintf(
                'Cannot normalize users.email: case-insensitive duplicate %s must be resolved first.',
                (string) $duplicate,
            ));
        }

        $this->update('{{%users}}', ['email' => new Expression('LOWER([[email]])')]);
        $this->db->createCommand(
            "CREATE UNIQUE INDEX IF NOT EXISTS ux_users_email_lower ON {$table} (LOWER(email))",
        )->execute();
    }

    private function upgradeCategories(): void
    {
        if ($this->tableExists('catalog') && !$this->tableExists('categories')) {
            $this->renameTable('{{%catalog}}', '{{%categories}}');
        }
        if (!$this->tableExists('categories')) {
            $this->createTable('{{%categories}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string(120)->notNull(),
                'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
        }
        if ((int) (new Query())->from('{{%categories}}')->count('*', $this->db) === 0) {
            $this->batchInsert('{{%categories}}', ['name'], [['Личное'], ['Работа']]);
        }
    }

    private function upgradeNotes(): void
    {
        $schema = $this->db->schema->getTableSchema($this->tableName('notes'), true);
        if ($schema === null || isset($schema->columns['category_id'])) {
            return;
        }

        $categoryId = (int) (new Query())
            ->select('id')
            ->from('{{%categories}}')
            ->orderBy(['id' => SORT_ASC])
            ->scalar($this->db);
        $this->addColumn('{{%notes}}', 'category_id', (string) $this->integer());
        $this->update('{{%notes}}', ['category_id' => $categoryId]);
        $this->alterColumn('{{%notes}}', 'category_id', (string) $this->integer()->notNull());
        $this->createIndex('idx_notes_category_id', '{{%notes}}', 'category_id');
        $this->createIndex('idx_notes_owner_category', '{{%notes}}', ['user_id', 'category_id']);
        $this->addForeignKey(
            'fk_notes_category_id',
            '{{%notes}}',
            'category_id',
            '{{%categories}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    private function upgradeAdmins(): void
    {
        if ($this->tableExists('admin') && !$this->tableExists('admins')) {
            $this->renameTable('{{%admin}}', '{{%admins}}');
        }
        $schema = $this->db->schema->getTableSchema($this->tableName('admins'), true);
        if ($schema === null) {
            return;
        }
        if (isset($schema->columns['remember_token']) && !isset($schema->columns['auth_key'])) {
            $this->renameColumn('{{%admins}}', 'remember_token', 'auth_key');
        }
        $schema = $this->db->schema->getTableSchema($this->tableName('admins'), true);
        if ($schema !== null && !isset($schema->columns['auth_key'])) {
            $this->addColumn('{{%admins}}', 'auth_key', (string) $this->string(64));
        }

        $this->update('{{%admins}}', ['name' => new Expression('login')], ['name' => null]);
        $this->update('{{%admins}}', ['role' => 'moderator'], ['role' => null]);
        $this->update('{{%admins}}', ['role' => 'moderator'], ['not in', 'role', ['admin', 'moderator']]);
        $ids = (new Query())->select('id')->from('{{%admins}}')->column($this->db);
        foreach ($ids as $id) {
            $row = (new Query())->select('auth_key')->from('{{%admins}}')->where(['id' => $id])->scalar($this->db);
            if ($row === null || $row === '') {
                $this->update(
                    '{{%admins}}',
                    ['auth_key' => Yii::$app->security->generateRandomString(64)],
                    ['id' => $id],
                );
            }
        }
        $this->alterColumn('{{%admins}}', 'name', (string) $this->string(160)->notNull());
        $this->alterColumn('{{%admins}}', 'auth_key', (string) $this->string(64)->notNull());
        $this->alterColumn('{{%admins}}', 'role', (string) $this->string(20)->notNull());
        $this->addCheck('chk_admins_role', '{{%admins}}', "role IN ('admin', 'moderator')");
    }

    private function createMessages(): void
    {
        if ($this->tableExists('messages')) {
            return;
        }
        $this->createTable('{{%messages}}', [
            'id' => $this->primaryKey(),
            'subject' => $this->string(255)->notNull(),
            'email' => $this->string(255)->notNull(),
            'phone' => $this->string(50),
            'message' => $this->text()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('new'),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx_messages_status_created', '{{%messages}}', ['status', 'created_at']);
        $this->addCheck('chk_messages_status', '{{%messages}}', "status IN ('new', 'read')");
    }

    private function tableExists(string $name): bool
    {
        return $this->db->schema->getTableSchema($this->tableName($name), true) !== null;
    }

    private function tableName(string $name): string
    {
        return $this->db->tablePrefix . $name;
    }
}

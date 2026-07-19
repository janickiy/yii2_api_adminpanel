<?php

declare(strict_types=1);

use yii\db\Expression;
use yii\db\Migration;
use yii\db\Query;

final class m260719_000002_add_unique_category_name extends Migration
{
    public function safeUp(): void
    {
        $duplicate = (new Query())
            ->select('name')
            ->from('{{%categories}}')
            ->groupBy('name')
            ->having(new Expression('COUNT(*) > 1'))
            ->scalar($this->db);

        if ($duplicate !== false) {
            throw new RuntimeException(sprintf(
                'Cannot add the category name constraint: duplicate "%s" must be resolved first.',
                (string) $duplicate,
            ));
        }

        $this->createIndex(
            'ux_categories_name',
            '{{%categories}}',
            'name',
            true,
        );
    }

    public function safeDown(): void
    {
        $this->dropIndex('ux_categories_name', '{{%categories}}');
    }
}

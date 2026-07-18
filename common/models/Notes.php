<?php

declare(strict_types=1);

namespace common\models;

use infrastructure\persistence\records\NoteRecord;
use Throwable;
use Yii;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;

/**
 * Backward-compatible ActiveRecord used by the admin UI.
 *
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property string $title
 * @property string $content
 * @property string $created_at
 * @property string $updated_at
 */
class Notes extends NoteRecord
{
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        $this->invalidateApiCache();
    }

    public function afterDelete(): void
    {
        $userId = (int) $this->user_id;
        parent::afterDelete();
        $this->invalidateApiCache($userId);
    }

    /**
     * @return ActiveQuery<User>
     */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return ActiveQuery<Catalog>
     */
    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Catalog::class, ['id' => 'category_id']);
    }

    private function invalidateApiCache(?int $userId = null): void
    {
        $userId ??= (int) $this->user_id;
        if ($userId < 1 || !Yii::$app->has('cache')) {
            return;
        }

        try {
            TagDependency::invalidate(Yii::$app->cache, 'notes:v1:user:' . $userId);
        } catch (Throwable $exception) {
            Yii::warning([
                'event' => 'notes.cache.admin_invalidation_failed',
                'exception_class' => $exception::class,
                'user_id' => $userId,
            ], 'application.admin');
        }
    }
}

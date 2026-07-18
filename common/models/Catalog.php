<?php

declare(strict_types=1);

namespace common\models;

use infrastructure\persistence\records\CategoryRecord;
use yii\db\ActiveQuery;

/**
 * Backward-compatible category ActiveRecord used by the admin UI.
 *
 * @property int $id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 */
class Catalog extends CategoryRecord
{
    /**
     * @return ActiveQuery<Notes>
     */
    public function getNotes(): ActiveQuery
    {
        return $this->hasMany(Notes::class, ['category_id' => 'id']);
    }
}

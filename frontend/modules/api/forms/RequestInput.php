<?php

declare(strict_types=1);

namespace frontend\modules\api\forms;

use yii\base\Model;

abstract class RequestInput extends Model
{
    public function formName(): string
    {
        return '';
    }
}

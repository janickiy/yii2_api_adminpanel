<?php

declare(strict_types=1);

namespace frontend\forms\api;

use yii\base\Model;

abstract class RequestInput extends Model
{
    public function formName(): string
    {
        return '';
    }
}

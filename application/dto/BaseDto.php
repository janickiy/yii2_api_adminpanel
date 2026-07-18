<?php

declare(strict_types=1);

namespace application\dto;

use yii\base\Model;

abstract class BaseDto extends Model
{
    public function formName(): string
    {
        return '';
    }
}

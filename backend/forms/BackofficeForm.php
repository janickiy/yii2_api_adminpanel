<?php

declare(strict_types=1);

namespace backend\forms;

use yii\base\Model;

abstract class BackofficeForm extends Model
{
    public function copyErrorsFrom(Model $source): void
    {
        $targetAttributes = array_flip($this->attributes());

        foreach ($source->getErrors() as $attribute => $errors) {
            $targetAttribute = isset($targetAttributes[$attribute]) ? $attribute : '';
            foreach ($errors as $error) {
                $this->addError($targetAttribute, $error);
            }
        }
    }
}

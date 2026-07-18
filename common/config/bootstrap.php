<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

Yii::setAlias('@root', $root);
Yii::setAlias('@common', $root . '/common');
Yii::setAlias('@frontend', $root . '/frontend');
Yii::setAlias('@backend', $root . '/backend');
Yii::setAlias('@api', $root . '/frontend/modules/api');
Yii::setAlias('@console', $root . '/console');
Yii::setAlias('@tests', $root . '/tests');
Yii::setAlias('@application', $root . '/application');
Yii::setAlias('@domain', $root . '/domain');
Yii::setAlias('@infrastructure', $root . '/infrastructure');

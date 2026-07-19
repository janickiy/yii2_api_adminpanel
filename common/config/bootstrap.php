<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

Yii::setAlias('@root', $root);
Yii::setAlias('@common', $root . '/common');
Yii::setAlias('@frontend', $root . '/frontend');
Yii::setAlias('@backend', $root . '/backend');
Yii::setAlias('@console', $root . '/console');
Yii::setAlias('@tests', $root . '/tests');

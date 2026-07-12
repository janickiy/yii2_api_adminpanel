<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/vendor/autoload.php';
require_once $root . '/common/config/env.php';

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require_once $root . '/vendor/yiisoft/yii2/Yii.php';
require_once $root . '/common/config/bootstrap.php';
require_once $root . '/backend/config/bootstrap.php';

return yii\helpers\ArrayHelper::merge(
    require $root . '/common/config/main.php',
    require $root . '/common/config/test.php',
    require $root . '/backend/config/main.php',
    require $root . '/backend/config/test.php',
);

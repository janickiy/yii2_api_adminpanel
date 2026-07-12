<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../common/config/env.php';

defined('YII_DEBUG') or define('YII_DEBUG', (bool) env('APP_DEBUG', true));
defined('YII_ENV') or define('YII_ENV', (string) env('APP_ENV', 'dev'));

require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../config/main.php',
);

(new yii\web\Application($config))->run();

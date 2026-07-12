<?php

declare(strict_types=1);

$root = dirname(__DIR__, 4);

require $root . '/vendor/autoload.php';
require $root . '/common/config/env.php';

defined('YII_DEBUG') or define('YII_DEBUG', (bool) env('APP_DEBUG', true));
defined('YII_ENV') or define('YII_ENV', (string) env('APP_ENV', 'dev'));

require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';
require $root . '/frontend/config/bootstrap.php';

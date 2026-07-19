<?php

declare(strict_types=1);

namespace backend\assets;

use yii\web\AssetBundle;
use yii\web\YiiAsset;

final class AdminAsset extends AssetBundle
{
    public $sourcePath = '@backend/web/js';

    public $js = [
        'admin-confirmation.js',
    ];

    public $depends = [
        YiiAsset::class,
        SweetAlertAsset::class,
    ];
}

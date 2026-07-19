<?php

declare(strict_types=1);

namespace backend\assets;

use yii\web\AssetBundle;

final class SweetAlertAsset extends AssetBundle
{
    public $sourcePath = '@npm/sweetalert2/dist';

    public $css = [
        'sweetalert2.min.css',
    ];

    public $js = [
        'sweetalert2.all.min.js',
    ];
}

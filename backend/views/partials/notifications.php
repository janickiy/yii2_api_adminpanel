<?php

declare(strict_types=1);

use yii\helpers\Html;

$flashes = [
    'message' => ['alert-warning', 'fa fa-exclamation-triangle'],
    'success' => ['alert-success', 'fa-fw fa fa-check'],
    'error' => ['alert-danger', 'fas fa-times'],
];
?>
<?php foreach ($flashes as $key => [$class, $icon]): ?>
    <?php if (Yii::$app->session->hasFlash($key)): ?>
        <div class="alert <?= $class ?>">
            <button class="close" data-dismiss="alert">x</button>
            <i class="<?= $icon ?>" aria-hidden="true"></i>
            <?= Html::encode((string) Yii::$app->session->getFlash($key)) ?>
        </div>
    <?php endif ?>
<?php endforeach ?>

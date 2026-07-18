<?php

declare(strict_types=1);

use yii\helpers\Html;

$flashes = [
    'message' => ['alert-warning', 'bi bi-exclamation-triangle'],
    'success' => ['alert-success', 'bi bi-check-circle'],
    'error' => ['alert-danger', 'bi bi-x-circle'],
];
?>
<?php foreach ($flashes as $key => [$class, $icon]): ?>
    <?php if (Yii::$app->session->hasFlash($key)): ?>
        <div class="alert <?= $class ?> alert-dismissible fade show" role="alert">
            <i class="<?= $icon ?> me-2" aria-hidden="true"></i>
            <?= Html::encode((string) Yii::$app->session->getFlash($key)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    <?php endif ?>
<?php endforeach ?>

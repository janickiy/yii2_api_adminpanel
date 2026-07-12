<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $title */

use yii\helpers\Html;

$this->title = $title;
$this->params['title'] = $title;
?>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Title</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                    <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            Start creating your amazing application!
        </div>
        <div class="card-footer">
            <?= Html::encode(Yii::$app->name) ?>
        </div>
    </div>
</section>

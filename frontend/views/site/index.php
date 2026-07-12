<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $apiUrl */

use yii\helpers\Html;

$this->title = Yii::$app->name;
?>
<section class="front-hero">
    <div class="container">
        <div class="front-hero__content">
            <p class="eyebrow">Yii2 Advanced</p>
            <h1><?= Html::encode(Yii::$app->name) ?></h1>
            <p class="lead">
                Public frontend is running. Admin panel, REST API, and Swagger documentation are available from one local stack.
            </p>
            <div class="actions">
                <a class="btn btn-primary" href="/login">Open admin</a>
                <a class="btn btn-outline-dark" href="/api/documentation">Open Swagger</a>
            </div>
        </div>
        <div class="front-status" aria-label="Application status">
            <div>
                <span>Frontend</span>
                <strong>OK</strong>
            </div>
            <div>
                <span>Admin</span>
                <strong>/login</strong>
            </div>
            <div>
                <span>API root</span>
                <strong><?= Html::encode(parse_url($apiUrl, PHP_URL_PATH) ?: '/api/v1') ?></strong>
            </div>
        </div>
    </div>
</section>

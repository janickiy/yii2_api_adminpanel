<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $message */

use yii\helpers\Html;

$this->title = 'Error';
?>
<section class="front-hero">
    <div class="container">
        <h1>Error</h1>
        <p class="lead"><?= Html::encode($message) ?></p>
        <a class="btn btn-primary" href="/">Back to frontend</a>
    </div>
</section>

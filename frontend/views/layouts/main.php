<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use frontend\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);
$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title) ?></title>
</head>
<body>
<?php $this->beginBody() ?>
<header class="site-header border-bottom">
    <nav class="container d-flex align-items-center justify-content-between py-3">
        <a class="brand" href="<?= Url::to(['/site/index']) ?>"><?= Html::encode(Yii::$app->name) ?></a>
        <div class="nav-links">
            <a href="<?= Url::to(['/site/index', '#' => 'feedback']) ?>">Обратная связь</a>
            <a href="/login">Админка</a>
            <a href="/api/documentation">Swagger</a>
            <a href="/api/v1/">API</a>
        </div>
    </nav>
</header>
<main>
    <?= $content ?>
</main>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

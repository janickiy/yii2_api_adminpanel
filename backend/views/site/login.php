<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\forms\AdminLoginForm $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <?php $this->head() ?>
    <title>Вход · <?= Html::encode(Yii::$app->name) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/admin-assets/site.css">
</head>
<body class="login-page bg-body-secondary">
<?php $this->beginBody() ?>
<div class="login-box">
    <div class="card card-outline card-primary shadow">
        <div class="card-header text-center py-4">
            <a href="<?= Url::to(['/site/login']) ?>" class="h2 text-decoration-none">
                <strong>Notes</strong> Admin
            </a>
        </div>
        <div class="card-body login-card-body">
            <p class="login-box-msg">Войдите в панель управления</p>

            <?php if (Yii::$app->session->hasFlash('error')): ?>
                <div class="alert alert-danger" role="alert">
                    <?= Html::encode((string) Yii::$app->session->getFlash('error')) ?>
                </div>
            <?php endif ?>

            <form action="<?= Url::to(['/site/login']) ?>" method="post">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

                <div class="input-group mb-1">
                    <div class="form-floating">
                        <input id="login" type="text" name="login" value="<?= Html::encode((string) $model->login) ?>"
                               class="form-control<?= $model->hasErrors('login') ? ' is-invalid' : '' ?>"
                               placeholder="Логин" autocomplete="username" required autofocus>
                        <label for="login">Логин</label>
                    </div>
                    <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                </div>
                <?php if ($model->hasErrors('login')): ?>
                    <div class="text-danger small mb-3"><?= Html::encode($model->getFirstError('login')) ?></div>
                <?php else: ?>
                    <div class="mb-3"></div>
                <?php endif ?>

                <div class="input-group mb-1">
                    <div class="form-floating">
                        <input id="password" type="password" name="password"
                               class="form-control<?= $model->hasErrors('password') ? ' is-invalid' : '' ?>"
                               placeholder="Пароль" autocomplete="current-password" required>
                        <label for="password">Пароль</label>
                    </div>
                    <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                </div>
                <?php if ($model->hasErrors('password')): ?>
                    <div class="text-danger small mb-3"><?= Html::encode($model->getFirstError('password')) ?></div>
                <?php else: ?>
                    <div class="mb-3"></div>
                <?php endif ?>

                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="form-check">
                        <input id="remember" type="checkbox" name="remember" value="1" class="form-check-input" <?= $model->remember ? 'checked' : '' ?>>
                        <label for="remember" class="form-check-label">Запомнить меня</label>
                    </div>
                    <button type="submit" class="btn btn-primary px-4">Войти</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/js/adminlte.min.js"></script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

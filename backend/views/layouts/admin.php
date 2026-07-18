<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use common\models\Admin;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);

$title = (string) ($this->params['title'] ?? $this->title ?? 'Панель управления');
$identity = Yii::$app->user->identity;
$admin = $identity instanceof Admin ? $identity : null;
$path = trim(Yii::$app->request->pathInfo, '/');
$isActive = static function (array $prefixes) use ($path): string {
    foreach ($prefixes as $prefix) {
        if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
            return ' active';
        }
    }

    return '';
};
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($title) ?> · <?= Html::encode(Yii::$app->name) ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/admin-assets/site.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<?php $this->beginBody() ?>
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body shadow-sm">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Открыть меню">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="<?= Url::to(['/dashboard/index']) ?>" class="nav-link">Панель управления</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-2 text-body-secondary d-none d-sm-block">
                    <?= Html::encode($admin?->name ?: $admin?->login ?: '') ?>
                </li>
                <li class="nav-item">
                    <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline']) ?>
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <button type="submit" class="nav-link border-0 bg-transparent" title="Выйти" aria-label="Выйти">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    </button>
                    <?= Html::endForm() ?>
                </li>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="<?= Url::to(['/dashboard/index']) ?>" class="brand-link text-decoration-none">
                <span class="brand-text fw-semibold">Notes Admin</span>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2" aria-label="Основная навигация">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="<?= Url::to(['/dashboard/index']) ?>" class="nav-link<?= $path === 'cp' ? ' active' : $isActive(['cp/dashboard']) ?>">
                            <i class="nav-icon bi bi-speedometer2" aria-hidden="true"></i>
                            <p>Обзор</p>
                        </a>
                    </li>
                    <?php if ($admin !== null && $admin->canAccess(Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR)): ?>
                        <li class="nav-header">КОНТЕНТ</li>
                        <li class="nav-item">
                            <a href="<?= Url::to(['/notes/index']) ?>" class="nav-link<?= $isActive(['cp/notes']) ?>">
                                <i class="nav-icon bi bi-journal-text" aria-hidden="true"></i>
                                <p>Заметки</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= Url::to(['/catalog/index']) ?>" class="nav-link<?= $isActive(['cp/categories', 'cp/catalog']) ?>">
                                <i class="nav-icon bi bi-tags" aria-hidden="true"></i>
                                <p>Категории</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= Url::to(['/messages/index']) ?>" class="nav-link<?= $isActive(['cp/messages']) ?>">
                                <i class="nav-icon bi bi-envelope" aria-hidden="true"></i>
                                <p>Сообщения</p>
                            </a>
                        </li>
                    <?php endif ?>
                    <?php if ($admin !== null && $admin->canAccess(Admin::ROLE_ADMIN)): ?>
                        <li class="nav-header">ДОСТУП</li>
                        <li class="nav-item">
                            <a href="<?= Url::to(['/users/index']) ?>" class="nav-link<?= $isActive(['cp/users']) ?>">
                                <i class="nav-icon bi bi-people" aria-hidden="true"></i>
                                <p>Пользователи</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= Url::to(['/admin/index']) ?>" class="nav-link<?= $isActive(['cp/admins', 'cp/admin']) ?>">
                                <i class="nav-icon bi bi-person-gear" aria-hidden="true"></i>
                                <p>Администраторы</p>
                            </a>
                        </li>
                    <?php endif ?>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-8"><h1 class="mb-0"><?= Html::encode($title) ?></h1></div>
                    <div class="col-sm-4 text-sm-end text-body-secondary">AdminLTE 4</div>
                </div>
            </div>
        </div>
        <div class="app-content pb-4">
            <div class="container-fluid">
                <?= $this->render('/partials/notifications') ?>
                <?= $content ?>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">Сервис заметок</div>
        <strong>&copy; <?= date('Y') ?> <?= Html::encode(Yii::$app->name) ?></strong>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/js/adminlte.min.js"></script>
<script>
document.addEventListener('click', function (event) {
    const link = event.target.closest('a[data-method]');
    if (!link) {
        return;
    }

    event.preventDefault();
    const confirmation = link.dataset.confirm;
    if (confirmation && !window.confirm(confirmation)) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'post';
    form.action = link.href;

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = <?= Json::htmlEncode(Yii::$app->request->csrfParam) ?>;
    csrf.value = <?= Json::htmlEncode(Yii::$app->request->csrfToken) ?>;
    form.appendChild(csrf);

    const method = (link.dataset.method || 'post').toUpperCase();
    if (method !== 'POST') {
        const override = document.createElement('input');
        override.type = 'hidden';
        override.name = '_method';
        override.value = method;
        form.appendChild(override);
    }

    document.body.appendChild(form);
    form.submit();
});
</script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use common\models\Admin;
use yii\helpers\Html;
use yii\helpers\Url;

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
$title = $this->params['title'] ?? $this->title;
$identity = Yii::$app->user->identity;
$admin = $identity instanceof Admin ? $identity : null;
$path = Yii::$app->request->pathInfo;
$isActive = static fn (string $prefix): string => str_starts_with($path, 'cp/' . $prefix) ? ' active' : '';
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <?php $this->head() ?>
    <title>Admin Panel | <?= Html::encode((string) $title) ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/plugins/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/plugins/toastr/toastr.min.css">
    <link rel="stylesheet" href="/plugins/flag-icon-css/css/flag-icon.min.css">
    <?= $this->blocks['css'] ?? '' ?>
    <script>let SITE_URL = "<?= Html::encode(Yii::$app->request->hostInfo) ?>";</script>
</head>
<body class="hold-transition sidebar-mini">
<?php $this->beginBody() ?>
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" title="развернуть" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="javascript:void(0);">
                    <i class="flag-icon flag-icon-ru"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right p-0">
                    <a data-id="en" href="javascript:void(0);" class="dropdown-item select-lang">
                        <i class="flag-icon flag-icon-us mr-2"></i> English
                    </a>
                    <a data-id="ru" href="javascript:void(0);" class="dropdown-item select-lang">
                        <i class="flag-icon flag-icon-ru mr-2"></i> Русский (Russian)
                    </a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" title="выйти" href="<?= Url::to(['/site/logout']) ?>" role="button">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="<?= Url::to(['/dashboard/index']) ?>" class="brand-link">
            <img src="/dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">AdminLTE 3</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="<?= Url::to(['/admin/edit', 'id' => Yii::$app->user->id]) ?>" class="d-block">
                        <?= Html::encode($admin === null ? '' : $admin->login) ?>
                        <?php if ($admin !== null && !empty($admin->name)): ?>
                            (<?= Html::encode($admin->name) ?>)
                        <?php endif ?>
                    </a>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="<?= Url::to(['/dashboard/index']) ?>" class="nav-link<?= $path === 'cp' ? ' active' : '' ?>" title="dashboard">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>dashboard</p>
                        </a>
                    </li>
                    <?php if ($admin !== null && $admin->canAccess('admin|moderator')): ?>
                        <li class="nav-item">
                            <a href="<?= Url::to(['/notes/index']) ?>" class="nav-link<?= $isActive('notes') ?>" title="заметки">
                                <i class="nav-icon fas fa-list"></i>
                                <p>заметки</p>
                            </a>
                        </li>
                    <?php endif ?>
                    <li class="nav-item">
                        <a href="<?= Url::to(['/catalog/index']) ?>" class="nav-link<?= $isActive('catalog') ?>" title="Каталог">
                            <i class="nav-icon fas fa-list"></i>
                            <p>каталог</p>
                        </a>
                    </li>
                    <?php if ($admin !== null && $admin->canAccess('admin')): ?>
                        <li class="nav-item">
                            <a href="<?= Url::to(['/admin/index']) ?>" class="nav-link<?= $isActive('admin') ?>" title="пользователи">
                                <i class="nav-icon fas fa-users"></i>
                                <p>пользователи</p>
                            </a>
                        </li>
                    <?php endif ?>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?= Html::encode((string) $title) ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= Url::to(['/dashboard/index']) ?>">Админ панель</a></li>
                            <li class="breadcrumb-item active"><?= Html::encode((string) $title) ?></li>
                        </ol>
                    </div>
                </div>
                <?= $this->render('/partials/notifications') ?>
            </div>
        </section>
        <?= $content ?>
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-block"><b></b></div>
        <strong>&copy; <?= date('Y') ?></strong>
    </footer>
    <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<script src="/plugins/jquery/jquery.min.js"></script>
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="/plugins/toastr/toastr.min.js"></script>
<script src="/dist/js/adminlte.min.js"></script>
<?= $this->blocks['js'] ?? '' ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

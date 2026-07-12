<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\forms\AdminForm $model */
/** @var array<string, string> $roles */
/** @var common\models\Admin|null $adminRecord */
/** @var string $title */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $title;
$this->params['title'] = $title;
$isUpdate = $model->id !== null;
$isSelf = $isUpdate && (int) $model->id === (int) Yii::$app->user->id;
$action = $isUpdate ? Url::to(['/admin/update']) : Url::to(['/admin/store']);
$error = static fn (string $attribute): string => $model->hasErrors($attribute) ? '<p class="text-danger">' . Html::encode($model->getFirstError($attribute)) . '</p>' : '';
?>
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <header class="card card-primary">
                    <form action="<?= $action ?>" method="post">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                        <?php if ($isUpdate): ?>
                            <?= Html::hiddenInput('id', (string) $model->id) ?>
                        <?php endif ?>
                        <div class="card-body">
                            <p>*-обязательные поля</p>
                            <div class="form-group">
                                <label for="name">имя</label>
                                <input id="name" type="text" name="name" value="<?= Html::encode((string) $model->name) ?>" class="form-control" placeholder="имя">
                                <?= $error('name') ?>
                            </div>
                            <div class="form-group">
                                <label for="login">логин</label>
                                <input id="login" type="text" name="login" value="<?= Html::encode((string) $model->login) ?>" class="form-control" placeholder="логин">
                                <?= $error('login') ?>
                            </div>
                            <?php if (!$isSelf): ?>
                                <div class="form-group">
                                    <label for="role">роль</label>
                                    <select id="role" name="role" class="custom-select">
                                        <option value="">роль</option>
                                        <?php foreach ($roles as $value => $label): ?>
                                            <option value="<?= Html::encode($value) ?>" <?= $model->role === $value ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                    <?= $error('role') ?>
                                </div>
                                <div class="form-group">
                                    <label for="password">пароль</label>
                                    <input id="password" type="password" name="password" class="form-control">
                                    <?= $error('password') ?>
                                </div>
                                <div class="form-group">
                                    <label for="password_again">повтор пароля</label>
                                    <input id="password_again" type="password" name="password_again" class="form-control">
                                    <?= $error('password_again') ?>
                                </div>
                            <?php endif ?>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><?= $isUpdate ? 'редактировать' : 'добавить' ?></button>
                            <a class="btn btn-default float-sm-right" href="<?= Url::to(['/admin/index']) ?>">назад</a>
                        </div>
                    </form>
                </header>
            </div>
        </div>
    </div>
</section>

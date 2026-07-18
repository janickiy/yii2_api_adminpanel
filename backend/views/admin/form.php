<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\forms\AdminForm $model */
/** @var array<string, string> $roles */
/** @var string $title */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $title;
$this->params['title'] = $title;
$isUpdate = $model->id !== null;
$isSelf = $isUpdate && (int) $model->id === (int) Yii::$app->user->id;
$action = $isUpdate ? ['/admin/update', 'id' => $model->id] : ['/admin/store'];
$inputClass = static fn (string $attribute): string => 'form-control' . ($model->hasErrors($attribute) ? ' is-invalid' : '');
$error = static fn (string $attribute): string => $model->hasErrors($attribute)
    ? Html::tag('div', Html::encode($model->getFirstError($attribute)), ['class' => 'invalid-feedback d-block'])
    : '';
?>
<div class="card"><div class="card-body">
    <form action="<?= Url::to($action) ?>" method="post" novalidate>
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
        <?php if ($isUpdate):
            ?><?= Html::hiddenInput('_method', 'PUT') ?><?= Html::activeHiddenInput($model, 'id') ?><?php
        endif ?>
        <?= Html::errorSummary($model, ['class' => 'alert alert-danger']) ?>
        <div class="row g-3">
            <div class="col-md-6"><div class="mb-3"><?= Html::activeLabel($model, 'name', ['class' => 'form-label']) ?><?= Html::activeTextInput($model, 'name', ['class' => $inputClass('name'), 'maxlength' => 160]) ?><?= $error('name') ?></div></div>
            <div class="col-md-6"><div class="mb-3"><?= Html::activeLabel($model, 'login', ['class' => 'form-label']) ?><?= Html::activeTextInput($model, 'login', ['class' => $inputClass('login'), 'maxlength' => 120, 'autocomplete' => 'username']) ?><?= $error('login') ?></div></div>
            <div class="col-md-6"><div class="mb-3"><?= Html::activeLabel($model, 'role', ['class' => 'form-label']) ?><?= Html::activeDropDownList($model, 'role', $roles, ['class' => 'form-select' . ($model->hasErrors('role') ? ' is-invalid' : ''), 'disabled' => $isSelf]) ?><?= $isSelf ? Html::activeHiddenInput($model, 'role') : '' ?><?= $error('role') ?><?php if ($isSelf):
                ?><div class="form-text">Нельзя изменить собственную роль.</div><?php
                                                    endif ?></div></div>
            <div class="col-md-6"></div>
            <div class="col-md-6"><div class="mb-3"><?= Html::activeLabel($model, 'password', ['class' => 'form-label']) ?><?= Html::activePasswordInput($model, 'password', ['class' => $inputClass('password'), 'value' => '', 'autocomplete' => 'new-password']) ?><?= $error('password') ?><?php if ($isUpdate):
                ?><div class="form-text">Оставьте пустым, чтобы сохранить текущий пароль.</div><?php
                                                    endif ?></div></div>
            <div class="col-md-6"><div class="mb-3"><?= Html::activeLabel($model, 'password_again', ['class' => 'form-label']) ?><?= Html::activePasswordInput($model, 'password_again', ['class' => $inputClass('password_again'), 'value' => '', 'autocomplete' => 'new-password']) ?><?= $error('password_again') ?></div></div>
        </div>
        <div class="d-flex gap-2 mt-3"><button type="submit" class="btn btn-primary"><?= $isUpdate ? 'Сохранить' : 'Создать' ?></button><?= Html::a('Отмена', ['/admin/index'], ['class' => 'btn btn-outline-secondary']) ?></div>
    </form>
</div></div>

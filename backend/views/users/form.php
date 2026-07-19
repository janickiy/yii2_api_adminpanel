<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\forms\UserForm $model */
/** @var string $title */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $title;
$this->params['title'] = $title;
$isUpdate = $model->id !== null;
$inputClass = static fn (string $attribute): string => 'form-control' . ($model->hasErrors($attribute) ? ' is-invalid' : '');
$error = static fn (string $attribute): string => $model->hasErrors($attribute)
    ? Html::tag('div', Html::encode($model->getFirstError($attribute)), ['class' => 'invalid-feedback d-block'])
    : '';
?>
<div class="card"><div class="card-body">
    <form action="<?= Url::to($isUpdate ? ['/users/update', 'id' => $model->id] : ['/users/store']) ?>" method="post" novalidate>
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
        <?php if ($isUpdate):
            ?><?= Html::hiddenInput('_method', 'PUT') ?><?= Html::activeHiddenInput($model, 'id') ?><?php
        endif ?>
        <?= Html::errorSummary($model, ['class' => 'alert alert-danger']) ?>
        <div class="row g-3">
            <div class="col-md-6"><div class="mb-3"><?= Html::activeLabel($model, 'name', ['class' => 'form-label']) ?><?= Html::activeTextInput($model, 'name', ['class' => $inputClass('name'), 'maxlength' => 160]) ?><?= $error('name') ?></div></div>
            <div class="col-md-6"><div class="mb-3"><?= Html::activeLabel($model, 'email', ['class' => 'form-label']) ?><?= Html::activeInput('email', $model, 'email', ['class' => $inputClass('email'), 'maxlength' => 255, 'autocomplete' => 'email']) ?><?= $error('email') ?></div></div>
            <div class="col-md-6"><div class="mb-3"><?= Html::activeLabel($model, 'password', ['class' => 'form-label']) ?><?= Html::activePasswordInput($model, 'password', ['class' => $inputClass('password'), 'value' => '', 'autocomplete' => 'new-password']) ?><?= $error('password') ?><?php if ($isUpdate):
                ?><div class="form-text">Оставьте пустым, чтобы сохранить текущий пароль.</div><?php
                                                    endif ?></div></div>
            <div class="col-md-6"><div class="mb-3"><?= Html::activeLabel($model, 'password_again', ['class' => 'form-label']) ?><?= Html::activePasswordInput($model, 'password_again', ['class' => $inputClass('password_again'), 'value' => '', 'autocomplete' => 'new-password']) ?><?= $error('password_again') ?></div></div>
        </div>
        <div class="d-flex gap-2 mt-3"><button type="submit" class="btn btn-primary"><?= $isUpdate ? 'Сохранить' : 'Создать' ?></button><?= Html::a('Отмена', ['/users/index'], ['class' => 'btn btn-outline-secondary']) ?></div>
    </form>
</div></div>

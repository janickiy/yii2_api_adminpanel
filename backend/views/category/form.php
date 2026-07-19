<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\forms\CategoryForm $model */
/** @var string $title */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $title;
$this->params['title'] = $title;
$isUpdate = $model->id !== null;
?>
<div class="card"><div class="card-body">
    <form action="<?= Url::to($isUpdate ? ['/category/update', 'id' => $model->id] : ['/category/store']) ?>" method="post" novalidate>
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
        <?php if ($isUpdate):
            ?><?= Html::hiddenInput('_method', 'PUT') ?><?= Html::activeHiddenInput($model, 'id') ?><?php
        endif ?>
        <?= Html::errorSummary($model, ['class' => 'alert alert-danger']) ?>
        <?= Html::activeLabel($model, 'name', ['class' => 'form-label']) ?>
        <?= Html::activeTextInput($model, 'name', ['class' => 'form-control' . ($model->hasErrors('name') ? ' is-invalid' : ''), 'maxlength' => 120, 'autofocus' => true]) ?>
        <?php if ($model->hasErrors('name')):
            ?><div class="invalid-feedback d-block"><?= Html::encode($model->getFirstError('name')) ?></div><?php
        endif ?>
        <div class="d-flex gap-2 mt-4"><button type="submit" class="btn btn-primary"><?= $isUpdate ? 'Сохранить' : 'Создать' ?></button><?= Html::a('Отмена', ['/category/index'], ['class' => 'btn btn-outline-secondary']) ?></div>
    </form>
</div></div>

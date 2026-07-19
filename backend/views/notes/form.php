<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\forms\NoteForm $model */
/** @var array<int|string, string> $categories */
/** @var string $title */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $title;
$this->params['title'] = $title;
$error = static fn (string $attribute): string => $model->hasErrors($attribute)
    ? Html::tag('div', Html::encode($model->getFirstError($attribute)), ['class' => 'invalid-feedback d-block'])
    : '';
?>
<div class="card"><div class="card-body">
    <form action="<?= Url::to(['/notes/update', 'id' => $model->id]) ?>" method="post" novalidate>
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
        <?= Html::hiddenInput('_method', 'PUT') ?>
        <?= Html::activeHiddenInput($model, 'id') ?>
        <?= Html::errorSummary($model, ['class' => 'alert alert-danger']) ?>
        <div class="mb-3"><?= Html::activeLabel($model, 'category_id', ['class' => 'form-label']) ?><?= Html::activeDropDownList($model, 'category_id', $categories, ['class' => 'form-select' . ($model->hasErrors('category_id') ? ' is-invalid' : ''), 'prompt' => 'Выберите категорию']) ?><?= $error('category_id') ?></div>
        <div class="mb-3"><?= Html::activeLabel($model, 'title', ['class' => 'form-label']) ?><?= Html::activeTextInput($model, 'title', ['class' => 'form-control' . ($model->hasErrors('title') ? ' is-invalid' : ''), 'maxlength' => 255]) ?><?= $error('title') ?></div>
        <div class="mb-3"><?= Html::activeLabel($model, 'content', ['class' => 'form-label']) ?><?= Html::activeTextarea($model, 'content', ['class' => 'form-control' . ($model->hasErrors('content') ? ' is-invalid' : ''), 'rows' => 8]) ?><?= $error('content') ?></div>
        <div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Сохранить</button><?= Html::a('Отмена', ['/notes/index'], ['class' => 'btn btn-outline-secondary']) ?></div>
    </form>
</div></div>

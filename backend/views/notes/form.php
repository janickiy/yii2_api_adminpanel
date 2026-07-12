<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\forms\NoteForm $model */
/** @var string $title */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $title;
$this->params['title'] = $title;
?>
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <header class="card card-primary">
                    <form action="<?= Url::to(['/notes/update']) ?>" method="post">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                        <?= Html::hiddenInput('id', (string) $model->id) ?>
                        <div class="card-body">
                            <p>*-обязательные поля</p>
                            <div class="form-group">
                                <label for="title">Название</label>
                                <input id="title" type="text" name="title" value="<?= Html::encode((string) $model->title) ?>" class="form-control" placeholder="Название">
                                <?php if ($model->hasErrors('title')): ?>
                                    <p class="text-danger"><?= Html::encode($model->getFirstError('title')) ?></p>
                                <?php endif ?>
                            </div>
                            <div class="form-group">
                                <label for="content">Заметка</label>
                                <textarea id="content" name="content" class="form-control" rows="6" placeholder="Заметка"><?= Html::encode((string) $model->content) ?></textarea>
                                <?php if ($model->hasErrors('content')): ?>
                                    <p class="text-danger"><?= Html::encode($model->getFirstError('content')) ?></p>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">редактировать</button>
                            <a class="btn btn-default float-sm-right" href="<?= Url::to(['/notes/index']) ?>">назад</a>
                        </div>
                    </form>
                </header>
            </div>
        </div>
    </div>
</section>

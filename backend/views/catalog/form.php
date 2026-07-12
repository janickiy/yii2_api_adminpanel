<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\forms\CatalogForm $model */
/** @var string $title */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $title;
$this->params['title'] = $title;
$isUpdate = $model->id !== null;
$action = $isUpdate ? Url::to(['/catalog/update']) : Url::to(['/catalog/store']);
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
                                <?php if ($model->hasErrors('name')): ?>
                                    <p class="text-danger"><?= Html::encode($model->getFirstError('name')) ?></p>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><?= $isUpdate ? 'редактировать' : 'добавить' ?></button>
                            <a class="btn btn-default float-sm-right" href="<?= Url::to(['/catalog/index']) ?>">назад</a>
                        </div>
                    </form>
                </header>
            </div>
        </div>
    </div>
</section>

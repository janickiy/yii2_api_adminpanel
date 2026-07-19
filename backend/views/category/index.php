<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $title */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\entities\Category;
use yii\helpers\Html;

$this->title = $title;
$this->params['title'] = $title;
/** @var Category[] $models */
$models = $dataProvider->getModels();
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title mb-0">Категории заметок</h2>
        <?= Html::a('<i class="bi bi-plus-lg" aria-hidden="true"></i> Добавить', ['/category/create'], ['class' => 'btn btn-primary btn-sm ms-auto']) ?>
    </div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Название</th><th class="text-end">Действия</th></tr></thead>
            <tbody>
            <?php foreach ($models as $model): ?>
                <tr><td><?= (int) $model->id ?></td><td><?= Html::encode($model->name) ?></td><td class="text-end"><div class="table-actions">
                    <?= Html::a('<i class="bi bi-pencil" aria-hidden="true"></i>', ['/category/edit', 'id' => $model->id], ['class' => 'btn btn-outline-primary btn-sm', 'title' => 'Редактировать']) ?>
                    <?= Html::a('<i class="bi bi-trash" aria-hidden="true"></i>', ['/category/destroy', 'id' => $model->id], ['class' => 'btn btn-outline-danger btn-sm', 'title' => 'Удалить', 'data' => ['method' => 'delete', 'confirm' => 'Удалить категорию?']]) ?>
                </div></td></tr>
            <?php endforeach ?>
            <?php if ($models === []):
                ?><tr><td colspan="3" class="text-center text-body-secondary py-4">Категории не найдены.</td></tr><?php
            endif ?>
            </tbody>
        </table>
    </div></div>
    <?= $this->render('/partials/pagination', ['dataProvider' => $dataProvider]) ?>
</div>

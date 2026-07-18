<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $title */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\Notes;
use yii\helpers\Html;
use yii\helpers\StringHelper;

$this->title = $title;
$this->params['title'] = $title;
/** @var Notes[] $models */
$models = $dataProvider->getModels();
?>
<div class="card">
    <div class="card-header"><h2 class="card-title mb-0">Все заметки пользователей</h2></div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Пользователь</th><th>Категория</th><th>Название</th><th>Текст</th><th class="text-end">Действия</th></tr></thead>
            <tbody>
            <?php foreach ($models as $model): ?>
                <tr>
                    <td><?= (int) $model->id ?></td>
                    <td><?= Html::encode($model->user?->name ?? '—') ?><?php if ($model->user !== null):
                        ?><br><small class="text-body-secondary"><?= Html::encode($model->user->email) ?></small><?php
                        endif ?></td>
                    <td><?= Html::encode($model->category?->name ?? '—') ?></td>
                    <td><?= Html::encode($model->title) ?></td>
                    <td><?= Html::encode(StringHelper::truncate($model->content, 100)) ?></td>
                    <td class="text-end"><div class="table-actions">
                        <?= Html::a('<i class="bi bi-pencil" aria-hidden="true"></i>', ['/notes/edit', 'id' => $model->id], ['class' => 'btn btn-outline-primary btn-sm', 'title' => 'Редактировать']) ?>
                        <?= Html::a('<i class="bi bi-trash" aria-hidden="true"></i>', ['/notes/destroy', 'id' => $model->id], ['class' => 'btn btn-outline-danger btn-sm', 'title' => 'Удалить', 'data' => ['method' => 'delete', 'confirm' => 'Удалить заметку?']]) ?>
                    </div></td>
                </tr>
            <?php endforeach ?>
            <?php if ($models === []):
                ?><tr><td colspan="6" class="text-center text-body-secondary py-4">Заметки не найдены.</td></tr><?php
            endif ?>
            </tbody>
        </table>
    </div></div>
    <?= $this->render('/partials/pagination', ['dataProvider' => $dataProvider]) ?>
</div>

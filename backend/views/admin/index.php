<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $title */
/** @var yii\data\ActiveDataProvider $dataProvider */

use backend\forms\AdminForm;
use common\entities\Admin;
use yii\helpers\Html;

$this->title = $title;
$this->params['title'] = $title;
$roleLabels = AdminForm::roleLabels();
/** @var Admin[] $models */
$models = $dataProvider->getModels();
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title mb-0">Учётные записи админки</h2>
        <?= Html::a('<i class="bi bi-plus-lg" aria-hidden="true"></i> Добавить', ['/admin/create'], ['class' => 'btn btn-primary btn-sm ms-auto']) ?>
    </div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Имя</th><th>Логин</th><th>Роль</th><th class="text-end">Действия</th></tr></thead>
            <tbody>
            <?php foreach ($models as $model): ?>
                <tr>
                    <td class="text-body-secondary"><?= (int) $model->id ?></td>
                    <td><?= Html::encode($model->name) ?></td>
                    <td><?= Html::encode($model->login) ?></td>
                    <td><span class="badge text-bg-<?= $model->role === Admin::ROLE_ADMIN ? 'primary' : 'secondary' ?>"><?= Html::encode($roleLabels[$model->role] ?? $model->role) ?></span></td>
                    <td class="text-end"><div class="table-actions">
                        <?= Html::a('<i class="bi bi-pencil" aria-hidden="true"></i>', ['/admin/edit', 'id' => $model->id], ['class' => 'btn btn-outline-primary btn-sm', 'title' => 'Редактировать']) ?>
                        <?php if ((int) $model->id !== (int) Yii::$app->user->id): ?>
                            <?= Html::a('<i class="bi bi-trash" aria-hidden="true"></i>', ['/admin/destroy', 'id' => $model->id], ['class' => 'btn btn-outline-danger btn-sm', 'title' => 'Удалить', 'data' => ['method' => 'delete', 'confirm' => 'Удалить администратора?']]) ?>
                        <?php endif ?>
                    </div></td>
                </tr>
            <?php endforeach ?>
            <?php if ($models === []):
                ?><tr><td colspan="5" class="text-center text-body-secondary py-4">Администраторы не найдены.</td></tr><?php
            endif ?>
            </tbody>
        </table>
    </div></div>
    <?= $this->render('/partials/pagination', ['dataProvider' => $dataProvider]) ?>
</div>

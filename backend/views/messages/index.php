<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $title */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\Message;
use yii\helpers\Html;

$this->title = $title;
$this->params['title'] = $title;
/** @var Message[] $models */
$models = $dataProvider->getModels();
?>
<div class="card">
    <div class="card-header"><h2 class="card-title mb-0">Обратная связь</h2></div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Тема</th><th>Email</th><th>Телефон</th><th>Статус</th><th>Получено</th><th class="text-end">Действия</th></tr></thead>
            <tbody>
            <?php foreach ($models as $model): ?>
                <?php $targetStatus = $model->status === Message::STATUS_NEW ? Message::STATUS_READ : Message::STATUS_NEW ?>
                <tr class="<?= $model->status === Message::STATUS_NEW ? 'table-primary' : '' ?>">
                    <td><?= (int) $model->id ?></td>
                    <td><?= Html::a(Html::encode($model->subject), ['/messages/view', 'id' => $model->id], ['class' => $model->status === Message::STATUS_NEW ? 'fw-semibold' : '']) ?></td>
                    <td><?= Html::mailto(Html::encode($model->email), $model->email) ?></td>
                    <td><?= $model->phone ? Html::encode($model->phone) : '—' ?></td>
                    <td><span class="badge text-bg-<?= $model->status === Message::STATUS_NEW ? 'primary' : 'secondary' ?>"><?= Html::encode(Message::statusLabels()[$model->status] ?? $model->status) ?></span></td>
                    <td><?= Html::encode(Yii::$app->formatter->asDatetime($model->created_at, 'php:d.m.Y H:i')) ?></td>
                    <td class="text-end"><div class="table-actions">
                        <?= Html::a('<i class="bi bi-eye" aria-hidden="true"></i>', ['/messages/view', 'id' => $model->id], ['class' => 'btn btn-outline-primary btn-sm', 'title' => 'Просмотреть']) ?>
                        <?= Html::a('<i class="bi bi-check2-circle" aria-hidden="true"></i>', ['/messages/status', 'id' => $model->id, 'status' => $targetStatus], ['class' => 'btn btn-outline-secondary btn-sm', 'title' => 'Изменить статус', 'data' => ['method' => 'post']]) ?>
                        <?= Html::a('<i class="bi bi-trash" aria-hidden="true"></i>', ['/messages/destroy', 'id' => $model->id], ['class' => 'btn btn-outline-danger btn-sm', 'title' => 'Удалить', 'data' => ['method' => 'delete', 'confirm' => 'Удалить сообщение?']]) ?>
                    </div></td>
                </tr>
            <?php endforeach ?>
            <?php if ($models === []):
                ?><tr><td colspan="7" class="text-center text-body-secondary py-4">Сообщений пока нет.</td></tr><?php
            endif ?>
            </tbody>
        </table>
    </div></div>
    <?= $this->render('/partials/pagination', ['dataProvider' => $dataProvider]) ?>
</div>

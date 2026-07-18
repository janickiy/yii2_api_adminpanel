<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Message $model */
/** @var string $title */

use common\models\Message;
use yii\helpers\Html;

$this->title = $title;
$this->params['title'] = $title;
$isRead = $model->status === Message::STATUS_READ;
?>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="card-title mb-0"><?= Html::encode($model->subject) ?></h2>
                <span class="badge text-bg-<?= $isRead ? 'secondary' : 'primary' ?> ms-auto">
                    <?= Html::encode(Message::statusLabels()[$model->status] ?? $model->status) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="message-body"><?= nl2br(Html::encode($model->message)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h2 class="card-title mb-0">Отправитель</h2></div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Email</dt>
                    <dd><?= Html::mailto(Html::encode($model->email), $model->email) ?></dd>
                    <dt>Телефон</dt>
                    <dd><?= $model->phone ? Html::encode($model->phone) : '—' ?></dd>
                    <dt>Получено</dt>
                    <dd><?= Html::encode(Yii::$app->formatter->asDatetime($model->created_at, 'php:d.m.Y H:i')) ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
    <?= Html::a('<i class="bi bi-arrow-left" aria-hidden="true"></i> К списку', ['/messages/index'], ['class' => 'btn btn-outline-secondary']) ?>
    <?= Html::a(
        $isRead ? 'Отметить новым' : 'Отметить просмотренным',
        ['/messages/status', 'id' => $model->id, 'status' => $isRead ? Message::STATUS_NEW : Message::STATUS_READ],
        ['class' => 'btn btn-primary', 'data' => ['method' => 'post']],
    ) ?>
    <?= Html::a('Удалить', ['/messages/destroy', 'id' => $model->id], [
        'class' => 'btn btn-outline-danger ms-sm-auto',
        'data' => ['method' => 'delete', 'confirm' => 'Удалить сообщение?'],
    ]) ?>
</div>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $title */
/** @var array<string, int|null> $counts */

use yii\helpers\Html;

$this->title = $title;
$this->params['title'] = $title;

$cards = [
    ['Заметки', $counts['notes'], 'bi-journal-text', ['/notes/index'], 'primary'],
    ['Категории', $counts['categories'], 'bi-tags', ['/catalog/index'], 'success'],
    ['Новые сообщения', $counts['newMessages'], 'bi-envelope', ['/messages/index'], 'warning'],
];
if ($counts['users'] !== null) {
    $cards[] = ['Пользователи', $counts['users'], 'bi-people', ['/users/index'], 'info'];
}
if ($counts['admins'] !== null) {
    $cards[] = ['Администраторы', $counts['admins'], 'bi-person-gear', ['/admin/index'], 'secondary'];
}
?>
<div class="row g-4">
    <?php foreach ($cards as [$label, $count, $icon, $route, $color]): ?>
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100 border-start border-4 border-<?= Html::encode($color) ?>">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi <?= Html::encode($icon) ?> fs-1 text-<?= Html::encode($color) ?>" aria-hidden="true"></i>
                    <div>
                        <div class="display-6 fw-semibold"><?= (int) $count ?></div>
                        <div class="text-body-secondary"><?= Html::encode($label) ?></div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <?= Html::a('Открыть раздел <i class="bi bi-arrow-right" aria-hidden="true"></i>', $route, [
                        'class' => 'text-decoration-none',
                    ]) ?>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\data\Pagination;
use yii\widgets\LinkPager;

$pagination = $dataProvider->getPagination();
$total = $dataProvider->getTotalCount();
$count = $dataProvider->getCount();
$begin = $pagination instanceof Pagination && $count > 0 ? $pagination->getOffset() + 1 : 0;
$end = $count > 0 ? $begin + $count - 1 : 0;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3">
    <span class="text-body-secondary">Показано <?= $begin ?>–<?= $end ?> из <?= $total ?></span>
    <?php if ($pagination instanceof Pagination): ?>
        <?= LinkPager::widget([
            'pagination' => $pagination,
            'options' => ['class' => 'pagination mb-0'],
            'linkOptions' => ['class' => 'page-link'],
            'pageCssClass' => 'page-item',
            'activePageCssClass' => 'active',
            'disabledPageCssClass' => 'disabled',
        ]) ?>
    <?php endif ?>
</div>

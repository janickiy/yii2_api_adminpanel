<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $apiUrl */
/** @var frontend\forms\FeedbackForm $feedbackForm */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = Yii::$app->name;
?>
<section class="front-hero">
    <div class="container">
        <div class="front-hero__content">
            <p class="eyebrow">Сервис заметок</p>
            <h1>Храните важное в одном месте</h1>
            <p class="lead">
                REST API с JWT-авторизацией помогает безопасно создавать и организовывать заметки по категориям.
            </p>
            <div class="actions">
                <a class="btn btn-primary" href="/api/documentation">Документация API</a>
                <a class="btn btn-outline-dark" href="#feedback">Связаться с нами</a>
            </div>
        </div>
        <div class="front-status" aria-label="Application status">
            <div>
                <span>API</span>
                <strong>REST</strong>
            </div>
            <div>
                <span>Авторизация</span>
                <strong>JWT</strong>
            </div>
            <div>
                <span>Адрес API</span>
                <strong><?= Html::encode(parse_url($apiUrl, PHP_URL_PATH) ?: '/api/v1') ?></strong>
            </div>
        </div>
    </div>
</section>

<section id="feedback" class="feedback-section">
    <div class="container">
        <div class="feedback-copy">
            <p class="eyebrow">Обратная связь</p>
            <h2>Есть вопрос или предложение?</h2>
            <p>Напишите нам. Авторизация не требуется — достаточно заполнить обязательные поля формы.</p>
        </div>

        <div class="feedback-card">
            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div class="alert alert-success" role="status">
                    <?= Html::encode((string) Yii::$app->session->getFlash('success')) ?>
                </div>
            <?php endif ?>

            <?php $form = ActiveForm::begin([
                'id' => 'feedback-form',
                'action' => ['/site/index'],
                'options' => ['novalidate' => true],
            ]) ?>

            <?= $form->field($feedbackForm, 'subject')->textInput([
                'maxlength' => true,
                'autocomplete' => 'off',
                'placeholder' => 'Например, вопрос по API',
            ]) ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <?= $form->field($feedbackForm, 'email')->input('email', [
                        'maxlength' => true,
                        'autocomplete' => 'email',
                        'placeholder' => 'name@example.com',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($feedbackForm, 'phone')->input('tel', [
                        'maxlength' => true,
                        'autocomplete' => 'tel',
                        'placeholder' => '+7 999 000-00-00',
                    ]) ?>
                </div>
            </div>

            <?= $form->field($feedbackForm, 'message')->textarea([
                'rows' => 6,
                'placeholder' => 'Расскажите подробнее…',
            ]) ?>

            <?= Html::submitButton('Отправить сообщение', ['class' => 'btn btn-primary btn-lg']) ?>

            <?php ActiveForm::end() ?>
        </div>
    </div>
</section>

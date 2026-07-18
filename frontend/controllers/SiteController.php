<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\filters\PublicRateLimitFilter;
use common\models\forms\FeedbackForm;
use Yii;
use yii\base\UserException;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    public function behaviors(): array
    {
        return [
            'publicRateLimit' => [
                'class' => PublicRateLimitFilter::class,
                'only' => ['index'],
                'limit' => 5,
                'window' => 60,
                'scope' => 'feedback',
            ],
        ];
    }

    public function actionIndex(): Response|string
    {
        $feedbackForm = new FeedbackForm();

        if (Yii::$app->request->isPost) {
            $feedbackForm->load(Yii::$app->request->post());

            if ($feedbackForm->save()) {
                Yii::info([
                    'event' => 'feedback.created',
                ], 'application.feedback');
                Yii::$app->session->setFlash('success', 'Спасибо! Ваше сообщение отправлено.');

                return $this->refresh();
            }
        }

        return $this->render('index', [
            'apiUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/api/v1']),
            'feedbackForm' => $feedbackForm,
        ]);
    }

    public function actionError(): string
    {
        $exception = Yii::$app->errorHandler->exception;
        $message = $exception instanceof UserException || YII_DEBUG
            ? ($exception?->getMessage() ?? 'Ошибка')
            : 'Произошла внутренняя ошибка. Попробуйте ещё раз позже.';

        return $this->render('error', [
            'message' => $message,
        ]);
    }
}

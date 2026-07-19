<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\filters\PublicRateLimitFilter;
use frontend\forms\FeedbackForm;
use frontend\services\FeedbackService;
use Yii;
use yii\base\Module;
use yii\base\UserException;
use yii\web\Controller;
use yii\web\Response;

final class SiteController extends Controller
{
    public function __construct(
        string $id,
        Module $module,
        private readonly FeedbackService $feedback,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

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

            if ($this->feedback->submit($feedbackForm)) {
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

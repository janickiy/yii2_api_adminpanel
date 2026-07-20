<?php

declare(strict_types=1);

namespace frontend\modules\api\components;

use Yii;
use yii\web\HttpException;
use yii\web\Response;

final class ApiAwareErrorHandler extends \yii\web\ErrorHandler
{
    private ?string $_apiRequestId = null;

    public function logException($exception): void
    {
        if (!$this->isApiRequest()) {
            parent::logException($exception);

            return;
        }

        $response = Yii::$app->getResponse();
        $response->setStatusCodeByException($exception);
        if ($response->statusCode >= 500) {
            parent::logException($exception);
            Yii::error([
                'event' => 'api.exception',
                'request_id' => $this->requestId(),
                'exception_class' => $exception::class,
            ], 'application.api');
        }
    }

    protected function renderException($exception): void
    {
        if (!$this->isApiRequest()) {
            parent::renderException($exception);

            return;
        }

        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $response->setStatusCodeByException($exception);

        $message = $exception instanceof HttpException
            ? $exception->getMessage()
            : 'Internal server error.';

        $error = [
            'status' => $response->statusCode,
            'message' => $message,
        ];
        if ($exception instanceof ValidationHttpException) {
            $error['fields'] = $exception->fields();
        } else {
            $requestId = $this->requestId();
            $response->headers->set('X-Request-ID', $requestId);
            $error['request_id'] = $requestId;
        }

        $response->data = ['error' => $error];
        $response->send();
    }

    private function requestId(): string
    {
        if ($this->_apiRequestId === null) {
            $candidate = (string) Yii::$app->request->headers->get('X-Request-ID', '');
            $this->_apiRequestId = preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,63}\z/D', $candidate) === 1
                ? $candidate
                : bin2hex(random_bytes(8));
        }

        return $this->_apiRequestId;
    }

    private function isApiRequest(): bool
    {
        return str_starts_with(Yii::$app->request->getPathInfo(), 'api/');
    }
}

<?php

declare(strict_types=1);

namespace common\filters;

use RuntimeException;
use Throwable;
use Yii;
use yii\base\ActionFilter;
use yii\caching\CacheInterface;
use yii\mutex\Mutex;
use yii\web\TooManyRequestsHttpException;

final class PublicRateLimitFilter extends ActionFilter
{
    public int $limit = 10;
    public int $window = 60;
    public string $scope = 'public';

    public function init(): void
    {
        parent::init();

        if ($this->limit < 1 || $this->window < 1 || trim($this->scope) === '') {
            throw new RuntimeException('Public rate-limit settings must be positive and scoped.');
        }
    }

    public function beforeAction($action): bool
    {
        if (!Yii::$app->request->isPost) {
            return parent::beforeAction($action);
        }

        $clientHash = hash('sha256', (string) Yii::$app->request->userIP);
        $bucket = intdiv(time(), $this->window);
        $key = ['public-rate-limit', $this->scope, $clientHash, $bucket];
        $lock = implode(':', ['public-rate-limit', $this->scope, $clientHash, (string) $bucket]);
        $retryAfter = $this->window - (time() % $this->window) + 1;
        $blocked = false;

        try {
            $cache = Yii::$app->get('cache');
            $mutex = Yii::$app->get('mutex');
            if (!$cache instanceof CacheInterface || !$mutex instanceof Mutex) {
                throw new RuntimeException('Cache and mutex components are required for public rate limiting.');
            }

            if (!$mutex->acquire($lock, 1)) {
                $blocked = true;
            } else {
                try {
                    $current = $cache->get($key);
                    $count = is_int($current) ? $current + 1 : 1;
                    if (!$cache->set($key, $count, $retryAfter)) {
                        throw new RuntimeException('Unable to persist the public rate-limit counter.');
                    }
                    $blocked = $count > $this->limit;
                } finally {
                    $mutex->release($lock);
                }
            }
        } catch (Throwable $exception) {
            Yii::warning([
                'event' => 'public_rate_limit.unavailable',
                'scope' => $this->scope,
                'exception_class' => $exception::class,
            ], 'application.security');

            return parent::beforeAction($action);
        }

        if (!$blocked) {
            return parent::beforeAction($action);
        }

        Yii::$app->response->headers->set('Retry-After', (string) $retryAfter);
        Yii::warning([
            'event' => 'public_rate_limit.exceeded',
            'scope' => $this->scope,
            'client_hash' => $clientHash,
        ], 'application.security');

        throw new TooManyRequestsHttpException('Too many requests. Try again later.');
    }
}

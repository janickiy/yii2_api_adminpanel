<?php

declare(strict_types=1);

namespace frontend\components\api;

use frontend\forms\api\RequestInput;
use Yii;

final class RequestInputFactory
{
    /**
     * @template T of RequestInput
     * @param class-string<T> $inputClass
     * @return T
     */
    public function fromBody(string $inputClass): RequestInput
    {
        $params = Yii::$app->request->getBodyParams();

        return $this->fromParams($inputClass, is_array($params) ? $params : []);
    }

    /**
     * @template T of RequestInput
     * @param class-string<T> $inputClass
     * @return T
     */
    public function fromQuery(string $inputClass): RequestInput
    {
        return $this->fromParams($inputClass, Yii::$app->request->get());
    }

    /**
     * @template T of RequestInput
     * @param class-string<T> $inputClass
     * @param array<string, mixed> $params
     * @return T
     */
    public function fromParams(string $inputClass, array $params): RequestInput
    {
        $input = new $inputClass();
        $input->load($params, '');

        if (!$input->validate()) {
            throw new ValidationHttpException($this->normalizeErrors($input->getErrors()));
        }

        return $input;
    }

    /**
     * @param array<string, array<string>> $errors
     * @return array<string, list<string>>
     */
    private function normalizeErrors(array $errors): array
    {
        $normalized = [];
        foreach ($errors as $field => $messages) {
            $normalized[$field] = array_values($messages);
        }

        return $normalized;
    }
}

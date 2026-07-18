<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

abstract class BaseWebController extends Controller
{
    public $layout = 'admin';

    protected string $permissions = '';

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'destroy' => ['DELETE'],
                ],
            ],
        ];
    }

    public function beforeAction($action): bool
    {
        if (Yii::$app->user->isGuest) {
            $this->redirect(['/site/login'])->send();

            return false;
        }

        if ($this->permissions !== '' && !$this->admin()->canAccess($this->permissions)) {
            throw new ForbiddenHttpException('Доступ запрещен.');
        }

        return parent::beforeAction($action);
    }

    protected function admin(): Admin
    {
        /** @var Admin $identity */
        $identity = Yii::$app->user->identity;

        return $identity;
    }

    protected function can(string $permissions): bool
    {
        return $this->admin()->canAccess($permissions);
    }

    protected function copyErrors(Model $source, Model $target): void
    {
        $targetAttributes = array_flip($target->attributes());

        foreach ($source->getErrors() as $attribute => $errors) {
            $targetAttribute = isset($targetAttributes[$attribute]) ? $attribute : '';
            foreach ($errors as $error) {
                $target->addError($targetAttribute, $error);
            }
        }
    }

    protected function deleteRecord(ActiveRecord $record): void
    {
        if ($record->delete() === 1) {
            return;
        }

        Yii::error([
            'event' => 'admin.delete_failed',
            'model' => $record::class,
            'primary_key' => $record->getPrimaryKey(),
            'errors' => $record->getErrors(),
        ], 'application.admin');

        throw new ServerErrorHttpException('Не удалось удалить запись.');
    }
}

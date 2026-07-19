<?php

declare(strict_types=1);

namespace common\repositories;

use common\entities\Admin;
use Throwable;
use yii\db\ActiveQuery;

final class AdminRepository extends AbstractActiveRecordRepository implements AdminRepositoryInterface
{
    public function findById(int $id): ?Admin
    {
        try {
            $admin = Admin::findOne(['id' => $id]);

            return $admin instanceof Admin ? $admin : null;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to find the administrator.', $exception);
        }
    }

    public function findByLogin(string $login): ?Admin
    {
        try {
            $admin = Admin::find()->where(['login' => $login])->one();

            return $admin instanceof Admin ? $admin : null;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to find the administrator by login.', $exception);
        }
    }

    public function query(): ActiveQuery
    {
        return Admin::find()->orderBy(['id' => SORT_DESC]);
    }

    public function count(): int
    {
        try {
            return (int) $this->query()->count();
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to count administrators.', $exception);
        }
    }

    public function save(Admin $admin): Admin
    {
        return $this->saveRecord($admin, 'Unable to save the administrator.');
    }

    public function delete(Admin $admin): void
    {
        $this->deleteRecord($admin, 'Unable to delete the administrator.');
    }
}

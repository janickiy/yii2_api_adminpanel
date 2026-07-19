<?php

declare(strict_types=1);

namespace common\repositories;

use common\entities\Admin;
use yii\db\ActiveQuery;

interface AdminRepositoryInterface
{
    public function findById(int $id): ?Admin;

    public function findByLogin(string $login): ?Admin;

    /**
     * @return ActiveQuery<Admin>
     */
    public function query(): ActiveQuery;

    public function count(): int;

    public function save(Admin $admin): Admin;

    public function delete(Admin $admin): void;
}

<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use common\models\Catalog;
use common\models\Notes;
use Closure;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Response;

class DatatableController extends BaseWebController
{
    public function actionNotes(): array
    {
        $this->requirePermission(Admin::ROLE_ADMIN . '|' . Admin::ROLE_MODERATOR);

        return $this->dataTable(
            Notes::find(),
            ['title', 'content'],
            ['title', 'content'],
            fn (Notes $row): array => [
                'id' => (int) $row->id,
                'title' => Html::encode($row->title),
                'content' => Html::encode($row->content),
                'actions' => $this->actionButtons('/notes/edit', '/notes/destroy', (int) $row->id),
            ],
        );
    }

    public function actionAdmin(): array
    {
        $this->requirePermission(Admin::ROLE_ADMIN);

        return $this->dataTable(
            Admin::find(),
            ['login', 'name', 'role'],
            ['login', 'name', 'role'],
            function (Admin $row): array {
                $delete = (int) $row->id === (int) Yii::$app->user->id
                    ? ''
                    : '<a title="удалить" class="btn btn-xs btn-danger deleteRow" href="' . Url::to(['/admin/destroy', 'id' => $row->id]) . '" data-id="' . (int) $row->id . '"><span class="fa fa-trash"></span></a>';

                return [
                    'id' => (int) $row->id,
                    'login' => Html::encode($row->login),
                    'name' => Html::encode((string) $row->name),
                    'role' => Admin::roleLabels()[$row->role] ?? Html::encode($row->role),
                    'action' => '<div class="nobr"><a title="редактировать" class="btn btn-xs btn-primary" href="' . Url::to(['/admin/edit', 'id' => $row->id]) . '"><span class="fa fa-edit"></span></a> &nbsp;' . $delete . '</div>',
                ];
            },
        );
    }

    public function actionCatalogs(): array
    {
        return $this->dataTable(
            Catalog::find(),
            ['name'],
            ['name'],
            fn (Catalog $row): array => [
                'id' => (int) $row->id,
                'name' => Html::encode($row->name),
                'actions' => $this->actionButtons('/catalog/edit', '/catalog/destroy', (int) $row->id),
            ],
        );
    }

    private function dataTable(ActiveQuery $query, array $searchColumns, array $orderColumns, Closure $rowMapper): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $draw = (int) $request->get('draw', 0);
        $start = max(0, (int) $request->get('start', 0));
        $length = (int) $request->get('length', 10);

        $total = (int) (clone $query)->count();
        $filteredQuery = clone $query;
        $search = trim((string) ($request->get('search')['value'] ?? ''));

        if ($search !== '') {
            $filter = ['or'];
            foreach ($searchColumns as $column) {
                $filter[] = ['like', $column, $search];
            }
            $filteredQuery->andWhere($filter);
        }

        $filtered = (int) (clone $filteredQuery)->count();
        $order = $request->get('order')[0] ?? null;
        $orderIndex = isset($order['column']) ? (int) $order['column'] : 0;
        $orderColumn = $orderColumns[$orderIndex] ?? $orderColumns[0] ?? 'id';
        $direction = strtolower((string) ($order['dir'] ?? 'asc')) === 'desc' ? SORT_DESC : SORT_ASC;
        $filteredQuery->orderBy([$orderColumn => $direction]);

        if ($length > -1) {
            $filteredQuery->offset($start)->limit($length);
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => array_map($rowMapper, $filteredQuery->all()),
        ];
    }

    private function actionButtons(string $editRoute, string $deleteRoute, int $id): string
    {
        $editUrl = Url::to([$editRoute, 'id' => $id]);
        $deleteUrl = Url::to([$deleteRoute, 'id' => $id]);

        return '<div class="nobr"><a title="редактировать" class="btn btn-xs btn-primary" href="' . $editUrl . '"><span class="fa fa-edit"></span></a> &nbsp;'
            . '<a title="удалить" class="btn btn-xs btn-danger deleteRow" href="' . $deleteUrl . '" data-id="' . $id . '"><span class="fa fa-trash"></span></a></div>';
    }

    private function requirePermission(string $permissions): void
    {
        if (!$this->can($permissions)) {
            throw new \yii\web\ForbiddenHttpException('Доступ запрещен.');
        }
    }
}

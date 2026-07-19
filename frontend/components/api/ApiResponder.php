<?php

declare(strict_types=1);

namespace frontend\components\api;

use common\dtos\AuthenticationResultDto;
use common\dtos\NotePageDto;
use common\entities\Category;
use common\entities\Note;
use common\entities\User;
use Yii;

final readonly class ApiResponder
{
    public function __construct(private ApiPresenter $presenter)
    {
    }

    /** @return array{data: array<string, mixed>} */
    public function user(User $user, int $statusCode = 200): array
    {
        $this->setStatusCode($statusCode);

        return ['data' => $this->presenter->user($user)];
    }

    /** @return array{data: array{token: string, token_type: string, user: array<string, mixed>}} */
    public function authentication(AuthenticationResultDto $result): array
    {
        return [
            'data' => [
                'token' => $result->token,
                'token_type' => 'Bearer',
                'user' => $this->presenter->user($result->user),
            ],
        ];
    }

    /** @return array{data: array<string, mixed>} */
    public function note(Note $note, int $statusCode = 200): array
    {
        $this->setStatusCode($statusCode);

        return ['data' => $this->presenter->note($note)];
    }

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{page: int, per_page: int, total: int, page_count: int}
     * }
     */
    public function notePage(NotePageDto $page): array
    {
        return [
            'data' => array_map($this->presenter->note(...), $page->items),
            'meta' => [
                'page' => $page->page,
                'per_page' => $page->perPage,
                'total' => $page->total,
                'page_count' => $page->total === 0
                    ? 0
                    : (int) ceil($page->total / $page->perPage),
            ],
        ];
    }

    /**
     * @param list<Category> $categories
     * @return array{data: list<array{id: int, name: string}>}
     */
    public function categories(array $categories): array
    {
        return [
            'data' => array_map($this->presenter->category(...), $categories),
        ];
    }

    public function noContent(): null
    {
        $this->setStatusCode(204);

        return null;
    }

    private function setStatusCode(int $statusCode): void
    {
        Yii::$app->response->statusCode = $statusCode;
    }
}

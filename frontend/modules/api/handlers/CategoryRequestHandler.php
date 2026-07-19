<?php

declare(strict_types=1);

namespace frontend\modules\api\handlers;

use application\services\CategoryService;
use frontend\modules\api\http\ApiResponder;

final readonly class CategoryRequestHandler
{
    public function __construct(
        private CategoryService $categoryService,
        private ApiResponder $responder,
    ) {
    }

    /** @return array{data: list<array{id: int, name: string}>} */
    public function index(): array
    {
        return $this->responder->categories($this->categoryService->list());
    }
}

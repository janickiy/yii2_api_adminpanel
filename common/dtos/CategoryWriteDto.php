<?php

declare(strict_types=1);

namespace common\dtos;

final class CategoryWriteDto
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

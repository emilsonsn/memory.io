<?php

namespace App\Events;

class CategoryExportRequested
{
    public function __construct(
        public readonly string $categoryId,
        public readonly string $userId,
    ) {}
}

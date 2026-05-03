<?php

namespace App\Listeners;

use App\Events\CategoryExportRequested;
use App\Services\Category\CategoryExportService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessCategoryExportRequested implements ShouldQueue
{
    public function __construct(private readonly CategoryExportService $categoryExportService) {}

    public function handle(CategoryExportRequested $event): void
    {
        $this->categoryExportService->exportAndNotify(
            categoryId: $event->categoryId,
            userId: $event->userId,
        );
    }
}

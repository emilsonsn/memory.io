<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Models\Memory;
use App\Services\Memory\MemoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryImportService
{
    public function __construct(private readonly MemoryService $memoryService) {}

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, Memory>
     */
    public function import(Category $category, array $files): array
    {
        return DB::transaction(function () use ($category, $files): array {
            $memories = [];

            foreach ($files as $file) {
                $memories[] = $this->memoryService->create([
                    'title' => $this->titleFromFile($file),
                    'content' => $this->contentFromFile($file),
                    'category_ids' => [$category->id],
                ]);
            }

            return $memories;
        });
    }

    private function titleFromFile(UploadedFile $file): string
    {
        $title = trim(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        return $title === ''
            ? 'Imported memory'
            : Str::limit($title, 255, '');
    }

    private function contentFromFile(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return '';
        }

        $content = file_get_contents($path);

        return $content === false ? '' : $content;
    }
}

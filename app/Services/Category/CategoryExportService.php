<?php

namespace App\Services\Category;

use App\Enums\NotificationType;
use App\Models\Category;
use App\Models\Memory;
use App\Models\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class CategoryExportService
{
    public function exportAndNotify(string $categoryId, string $userId): void
    {
        $rootCategory = Category::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('id', $categoryId)
            ->firstOrFail();

        $tempBaseDir = storage_path('app/tmp/category-exports/'.Str::uuid()->toString());
        File::ensureDirectoryExists($tempBaseDir);

        $rootFolderName = $this->sanitizeDirectoryName($rootCategory->label, 'category-'.$rootCategory->id);
        $rootFolderPath = $tempBaseDir.DIRECTORY_SEPARATOR.$rootFolderName;
        File::ensureDirectoryExists($rootFolderPath);

        $this->buildCategoryTree(
            category: $rootCategory,
            directoryPath: $rootFolderPath,
            userId: $userId,
        );

        $zipRelativePath = sprintf(
            'exports/categories/%s/category-%s-%s.zip',
            $userId,
            $rootCategory->id,
            now()->format('YmdHis'),
        );

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        $zipAbsolutePath = $publicDisk->path($zipRelativePath);
        File::ensureDirectoryExists(dirname($zipAbsolutePath));

        $zip = new ZipArchive();
        $opened = $zip->open($zipAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            throw new RuntimeException('Could not create category export archive.');
        }

        $this->addDirectoryToZip($zip, $tempBaseDir, '');
        $zip->close();

        File::deleteDirectory($tempBaseDir);

        $publicUrl = $publicDisk->url($zipRelativePath);
        $downloadUrl = Str::startsWith($publicUrl, ['http://', 'https://'])
            ? $publicUrl
            : rtrim((string) config('app.url'), '/').$publicUrl;

        Notification::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'title' => sprintf('A exportacao da categoria "%s" foi concluida.', $rootCategory->label),
            'url' => $downloadUrl,
            'type' => NotificationType::PROCESS,
            'seen' => false,
        ]);
    }

    private function buildCategoryTree(Category $category, string $directoryPath, string $userId): void
    {
        $memoryIds = Memory::withoutGlobalScopes()
            ->newQuery()
            ->join('category_memory', 'category_memory.memory_id', '=', 'memories.id')
            ->where('category_memory.category_id', $category->id)
            ->where('memories.user_id', $userId)
            ->whereNull('memories.deleted_at')
            ->pluck('memories.id');

        $memories = Memory::withoutGlobalScopes()
            ->whereIn('id', $memoryIds)
            ->get();

        foreach ($memories as $memory) {
            $export = $this->memoryToTextFile($memory);
            $filePath = $this->resolveUniqueFilePath($directoryPath, $export['filename']);
            File::put($filePath, $export['content']);
        }

        $children = Category::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('parent_id', $category->id)
            ->get();

        foreach ($children as $child) {
            $childDirectoryName = $this->sanitizeDirectoryName($child->label, 'category-'.$child->id);
            $childDirectoryPath = $directoryPath.DIRECTORY_SEPARATOR.$childDirectoryName;
            File::ensureDirectoryExists($childDirectoryPath);

            $this->buildCategoryTree(
                category: $child,
                directoryPath: $childDirectoryPath,
                userId: $userId,
            );
        }
    }

    /**
     * @return array{filename: string, content: string}
     */
    private function memoryToTextFile(Memory $memory): array
    {
        $title = trim((string) $memory->title);
        $safeTitle = Str::of($title)
            ->replaceMatches('/[\\\\\/:*?"<>|\x00-\x1F]+/', '-')
            ->trim()
            ->toString();

        if ($safeTitle === '') {
            $safeTitle = 'memory-'.$memory->id;
        }

        return [
            'filename' => $safeTitle.'.txt',
            'content' => $title."\n".$memory->content,
        ];
    }

    private function sanitizeDirectoryName(?string $name, string $fallback): string
    {
        $safe = Str::of((string) $name)
            ->replaceMatches('/[\\\\\/:*?"<>|\x00-\x1F]+/', '-')
            ->trim()
            ->toString();

        return $safe === '' ? $fallback : $safe;
    }

    private function resolveUniqueFilePath(string $directoryPath, string $filename): string
    {
        $path = $directoryPath.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($path)) {
            return $path;
        }

        $name = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $counter = 1;

        do {
            $candidate = sprintf(
                '%s (%d).%s',
                $name,
                $counter,
                $extension,
            );

            $path = $directoryPath.DIRECTORY_SEPARATOR.$candidate;
            $counter++;
        } while (File::exists($path));

        return $path;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $absolutePath, string $zipPrefix): void
    {
        $items = File::files($absolutePath);

        foreach ($items as $file) {
            $zip->addFile($file->getPathname(), $zipPrefix.$file->getFilename());
        }

        $directories = File::directories($absolutePath);

        foreach ($directories as $directory) {
            $directoryName = basename($directory);
            $nextPrefix = $zipPrefix.$directoryName.'/';
            $zip->addEmptyDir($nextPrefix);
            $this->addDirectoryToZip($zip, $directory, $nextPrefix);
        }
    }
}

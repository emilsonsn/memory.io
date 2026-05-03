<?php

namespace App\Jobs;

use App\Models\Memory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteExpiredMemoriesJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Memory::withoutGlobalScopes()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', Carbon::today())
            ->whereNull('deleted_at')
            ->cursor()
            ->each(function (Memory $memory): void {
                $snapshot = [
                    'title' => $memory->title,
                    'content' => $memory->content,
                    'due_date' => $memory->due_date?->toISOString(),
                    'category_ids' => DB::table('category_memory')
                        ->where('memory_id', $memory->id)
                        ->pluck('category_id')
                        ->all(),
                ];

                $memory->delete();

                activity('audit')
                    ->performedOn($memory)
                    ->event('memory.deleted_due_date')
                    ->withProperties([
                        'old' => $snapshot,
                        'new' => null,
                        'changed_fields' => array_keys($snapshot),
                        'reason' => 'due_date_expired',
                        'deleted_by' => 'scheduler',
                    ])
                    ->log('memory.deleted_due_date');
            });
    }
}

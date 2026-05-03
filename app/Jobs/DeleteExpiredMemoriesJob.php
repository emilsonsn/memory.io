<?php

namespace App\Jobs;

use App\Enums\MemoryAuditEvent;
use App\Enums\MemoryDeletionReason;
use App\Enums\SystemActor;
use App\Events\MemoryDeletedByScheduler;
use App\Models\Memory;
use App\Support\VersionedCache;
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

                event(new MemoryDeletedByScheduler(
                    memoryId: (string) $memory->id,
                    userId: (string) $memory->user_id,
                    memoryTitle: $memory->title,
                ));

                activity('audit')
                    ->performedOn($memory)
                    ->event(MemoryAuditEvent::DELETED_DUE_DATE->value)
                    ->withProperties([
                        'old' => $snapshot,
                        'new' => null,
                        'changed_fields' => array_keys($snapshot),
                        'reason' => MemoryDeletionReason::DUE_DATE_EXPIRED->value,
                        'deleted_by' => SystemActor::SCHEDULER->value,
                    ])
                    ->log(MemoryAuditEvent::DELETED_DUE_DATE->value);

                VersionedCache::bump('memories.list', $memory->user_id);
                VersionedCache::bump('memories.logs', $memory->user_id);
            });
    }
}

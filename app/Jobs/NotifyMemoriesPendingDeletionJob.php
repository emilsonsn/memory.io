<?php

namespace App\Jobs;

use App\Events\MemoryDeletionReminderTriggered;
use App\Models\Memory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class NotifyMemoriesPendingDeletionJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Memory::withoutGlobalScopes()
            ->whereNotNull('due_date')
            ->whereDate('due_date', Carbon::tomorrow())
            ->whereNull('deleted_at')
            ->cursor()
            ->each(function (Memory $memory): void {
                event(new MemoryDeletionReminderTriggered(
                    memoryId: (string) $memory->id,
                    userId: (string) $memory->user_id,
                    memoryTitle: $memory->title,
                ));
            });
    }
}

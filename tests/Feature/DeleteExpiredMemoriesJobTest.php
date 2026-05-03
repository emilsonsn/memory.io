<?php

namespace Tests\Feature;

use App\Enums\MemoryAuditEvent;
use App\Enums\MemoryDeletionReason;
use App\Enums\NotificationType;
use App\Enums\SystemActor;
use App\Jobs\DeleteExpiredMemoriesJob;
use App\Jobs\NotifyMemoriesPendingDeletionJob;
use App\Models\Memory;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DeleteExpiredMemoriesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_deletes_only_expired_memories_and_registers_logs(): void
    {
        $user = User::factory()->create();

        $expiredMemory = Memory::factory()->for($user)->create([
            'title' => 'Expired memory',
            'content' => 'Should be deleted by scheduler',
            'due_date' => now()->subDay(),
        ]);

        $futureMemory = Memory::factory()->for($user)->create([
            'title' => 'Future memory',
            'content' => 'Should remain active',
            'due_date' => now()->addDay(),
        ]);

        (new DeleteExpiredMemoriesJob)->handle();

        $this->assertSoftDeleted('memories', [
            'id' => $expiredMemory->id,
        ]);

        $this->assertDatabaseHas('memories', [
            'id' => $futureMemory->id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'audit',
            'event' => MemoryAuditEvent::DELETED_DUE_DATE->value,
            'description' => MemoryAuditEvent::DELETED_DUE_DATE->value,
            'subject_type' => Memory::class,
            'subject_id' => $expiredMemory->id,
            'causer_type' => null,
            'causer_id' => null,
        ]);

        $activity = Activity::query()
            ->where('event', MemoryAuditEvent::DELETED_DUE_DATE->value)
            ->where('subject_id', $expiredMemory->id)
            ->firstOrFail();

        $this->assertSame('Expired memory', data_get($activity->properties->toArray(), 'old.title'));
        $this->assertSame(MemoryDeletionReason::DUE_DATE_EXPIRED->value, data_get($activity->properties->toArray(), 'reason'));
        $this->assertSame(SystemActor::SCHEDULER->value, data_get($activity->properties->toArray(), 'deleted_by'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'A memoria "Expired memory" foi apagada automaticamente por vencimento.',
            'url' => '/memories',
            'type' => NotificationType::PROCESS->value,
            'seen' => false,
        ]);
    }

    public function test_job_notifies_one_day_before_memory_deletion(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00'));

        $user = User::factory()->create();

        $tomorrowMemory = Memory::factory()->for($user)->create([
            'title' => 'Tomorrow memory',
            'content' => 'Will expire tomorrow',
            'due_date' => now()->addDay(),
        ]);

        Memory::factory()->for($user)->create([
            'title' => 'Today memory',
            'content' => 'Will expire today',
            'due_date' => now(),
        ]);

        Memory::factory()->for($user)->create([
            'title' => 'Later memory',
            'content' => 'Will expire in two days',
            'due_date' => now()->addDays(2),
        ]);

        (new NotifyMemoriesPendingDeletionJob)->handle();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'A memoria "Tomorrow memory" sera apagada automaticamente em 1 dia.',
            'url' => "/memories/{$tomorrowMemory->id}",
            'type' => NotificationType::PROCESS->value,
            'seen' => false,
        ]);

        $this->assertSame(
            1,
            Notification::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('type', NotificationType::PROCESS->value)
                ->count(),
        );

        Carbon::setTestNow();
    }
}

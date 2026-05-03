<?php

namespace Tests\Feature;

use App\Jobs\DeleteExpiredMemoriesJob;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        (new DeleteExpiredMemoriesJob())->handle();

        $this->assertSoftDeleted('memories', [
            'id' => $expiredMemory->id,
        ]);

        $this->assertDatabaseHas('memories', [
            'id' => $futureMemory->id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'audit',
            'event' => 'memory.deleted_due_date',
            'description' => 'memory.deleted_due_date',
            'subject_type' => Memory::class,
            'subject_id' => $expiredMemory->id,
            'causer_type' => null,
            'causer_id' => null,
        ]);

        $activity = Activity::query()
            ->where('event', 'memory.deleted_due_date')
            ->where('subject_id', $expiredMemory->id)
            ->firstOrFail();

        $this->assertSame('Expired memory', data_get($activity->properties->toArray(), 'old.title'));
        $this->assertSame('due_date_expired', data_get($activity->properties->toArray(), 'reason'));
        $this->assertSame('scheduler', data_get($activity->properties->toArray(), 'deleted_by'));
    }
}

<?php

namespace App\Events;

class MemoryDeletionReminderTriggered
{
    public function __construct(
        public readonly string $memoryId,
        public readonly string $userId,
        public readonly string $memoryTitle,
    ) {}
}

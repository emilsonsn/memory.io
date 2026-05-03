<?php

namespace App\Events;

class MemoryDeletedByScheduler
{
    public function __construct(
        public readonly string $memoryId,
        public readonly string $userId,
        public readonly string $memoryTitle,
    ) {}
}

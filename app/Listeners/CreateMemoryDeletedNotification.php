<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\MemoryDeletedByScheduler;
use App\Models\Notification;

class CreateMemoryDeletedNotification
{
    public function handle(MemoryDeletedByScheduler $event): void
    {
        $title = sprintf(
            'A memoria "%s" foi apagada automaticamente por vencimento.',
            $event->memoryTitle,
        );

        $exists = Notification::withoutGlobalScopes()
            ->where('user_id', $event->userId)
            ->where('title', $title)
            ->where('url', '/memories')
            ->where('type', NotificationType::Process)
            ->exists();

        if ($exists) {
            return;
        }

        $notification = new Notification();
        $notification->user_id = $event->userId;
        $notification->title = $title;
        $notification->url = '/memories';
        $notification->type = NotificationType::Process;
        $notification->seen = false;
        $notification->save();
    }
}

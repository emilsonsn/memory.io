<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\MemoryDeletionReminderTriggered;
use App\Models\Notification;

class CreateMemoryDeletionReminderNotification
{
    public function handle(MemoryDeletionReminderTriggered $event): void
    {
        $title = sprintf(
            'A memoria "%s" sera apagada automaticamente em 1 dia.',
            $event->memoryTitle,
        );
        $url = sprintf('/memories/%s', $event->memoryId);

        $exists = Notification::withoutGlobalScopes()
            ->where('user_id', $event->userId)
            ->where('title', $title)
            ->where('url', $url)
            ->where('type', NotificationType::PROCESS->value)
            ->exists();

        if ($exists) {
            return;
        }

        $notification = new Notification();
        $notification->user_id = $event->userId;
        $notification->title = $title;
        $notification->url = $url;
        $notification->type = NotificationType::PROCESS;
        $notification->seen = false;
        $notification->save();
    }
}

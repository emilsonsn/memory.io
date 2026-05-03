<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Notification $notification): bool
    {
        return $user->isAdmin() || $notification->user_id === $user->id;
    }

    public function read(User $user, Notification $notification): bool
    {
        return $this->view($user, $notification);
    }

    public function readMany(User $user): bool
    {
        return true;
    }
}

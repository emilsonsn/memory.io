<?php

namespace App\Policies;

use App\Models\Memory;
use App\Models\User;

class MemoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Memory $memory): bool
    {
        return $user->isAdmin() || $memory->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Memory $memory): bool
    {
        return $user->isAdmin() || $memory->user_id === $user->id;
    }

    public function delete(User $user, Memory $memory): bool
    {
        return $user->isAdmin() || $memory->user_id === $user->id;
    }

    public function logs(User $user, Memory $memory): bool
    {
        return $this->view($user, $memory);
    }

    public function export(User $user, Memory $memory): bool
    {
        return $this->view($user, $memory);
    }

}

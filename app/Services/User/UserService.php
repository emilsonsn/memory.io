<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class UserService
{
    private User $user;

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function object(): User
    {
        return $this->user->fresh();
    }

    public function verifyUserIsSet(): void
    {
        if (! isset($this->user)) {
            throw new RuntimeException('User is not set.');
        }
    }

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        $this->user = User::create($data);

        return $this->object();
    }

    public function update(array $data): User
    {
        $this->verifyUserIsSet();

        $this->user->update($data);

        return $this->object();
    }

    public function delete(): self
    {
        $this->verifyUserIsSet();
        $this->user->delete();

        return $this;
    }
}

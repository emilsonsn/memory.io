<?php

namespace App\Services\User;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;
use Spatie\Permission\Models\Role;

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
        return $this->user
            ->fresh()
            ->load([
                'plan',
            ]);
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
            ->with('plan')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        $this->user = User::create($data);
        $this->user->assignRole(Role::findOrCreate('user'));

        return $this->object();
    }

    public function update(array $data): User
    {
        $this->verifyUserIsSet();
        unset($data['plan_id']);

        $this->user->update($data);

        return $this->object();
    }

    public function assignPlan(Plan|string|null $plan): User
    {
        $this->verifyUserIsSet();

        $this->user->forceFill([
            'plan_id' => $plan instanceof Plan ? $plan->id : $plan,
        ])->save();

        return $this->object();
    }

    public function delete(): self
    {
        $this->verifyUserIsSet();
        $this->user->delete();

        return $this;
    }
}

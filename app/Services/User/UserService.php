<?php

namespace App\Services\User;

use App\Models\Plan;
use App\Models\User;
use App\Support\VersionedCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;
use Spatie\Permission\Models\Role;

class UserService
{
    private const LIST_CACHE_TTL_SECONDS = 300;

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
        $page = request()->integer('page', 1);

        return VersionedCache::remember(
            namespace: 'users.list',
            params: [
                'per_page' => $perPage,
                'page' => $page,
            ],
            ttlSeconds: self::LIST_CACHE_TTL_SECONDS,
            callback: static fn () => User::query()
                ->with('plan')
                ->latest()
                ->paginate($perPage),
        );
    }

    public function create(array $data): User
    {
        $this->user = User::create($data);
        $this->user->assignRole(Role::findOrCreate('user'));

        VersionedCache::bump('users.list');

        return $this->object();
    }

    public function update(array $data): User
    {
        $this->verifyUserIsSet();
        unset($data['plan_id']);

        $this->user->update($data);

        VersionedCache::bump('users.list');

        return $this->object();
    }

    public function assignPlan(Plan|string|null $plan): User
    {
        $this->verifyUserIsSet();

        $this->user->forceFill([
            'plan_id' => $plan instanceof Plan ? $plan->id : $plan,
        ])->save();

        VersionedCache::bump('users.list');

        return $this->object();
    }

    public function delete(): self
    {
        $this->verifyUserIsSet();
        $this->user->delete();

        VersionedCache::bump('users.list');

        return $this;
    }
}

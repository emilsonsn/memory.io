<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Support\VersionedCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    private const LIST_CACHE_TTL_SECONDS = 60;

    private Notification $notification;

    public function setNotification(Notification $notification): self
    {
        $this->notification = $notification;

        return $this;
    }

    public function object(): Notification
    {
        return $this->notification->fresh();
    }

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return VersionedCache::remember(
            namespace: 'notifications.list',
            params: [
                'per_page' => $perPage,
                'page' => request()->integer('page', 1),
            ],
            ttlSeconds: self::LIST_CACHE_TTL_SECONDS,
            callback: static fn () => Notification::query()
                ->latest()
                ->paginate($perPage),
            scope: auth()->id(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification
    {
        $this->notification = Notification::query()->create($data);

        return $this->object();
    }

    public function read(Notification $notification): Notification
    {
        if (! $notification->seen) {
            $notification->update([
                'seen' => true,
            ]);
        }

        $this->notification = $notification;

        return $this->object();
    }
}

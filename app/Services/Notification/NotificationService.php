<?php

namespace App\Services\Notification;

use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{
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
        return Notification::query()
            ->latest()
            ->paginate($perPage);
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

    /**
     * @param  array<int, string>  $ids
     * @return Collection<int, Notification>
     */
    public function readMany(array $ids): Collection
    {
        $notifications = Notification::query()
            ->whereIn('id', $ids)
            ->get();

        $notifications->each(function (Notification $notification): void {
            if (! $notification->seen) {
                $notification->update([
                    'seen' => true,
                ]);
            }
        });

        return Notification::query()
            ->whereIn('id', $ids)
            ->get();
    }
}

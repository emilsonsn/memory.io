<?php

namespace App\Services\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait AuditsActivities
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<int, string>
     */
    protected function resolveChangedFields(array $before, array $after): array
    {
        return Collection::make(array_keys($after))
            ->filter(fn (string $field): bool => ($before[$field] ?? null) !== ($after[$field] ?? null))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected function audit(string $event, Model $subject, array $properties, string $logName = 'audit'): void
    {
        $activity = activity($logName)
            ->performedOn($subject)
            ->event($event)
            ->withProperties($properties);

        $causer = auth()->user();
        if ($causer !== null) {
            $activity->causedBy($causer);
        }

        $activity->log($event);
    }
}

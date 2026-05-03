<?php

use App\Jobs\DeleteExpiredMemoriesJob;
use App\Jobs\NotifyMemoriesPendingDeletionJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new NotifyMemoriesPendingDeletionJob())
    ->dailyAt('00:05')
    ->withoutOverlapping();

Schedule::job(new DeleteExpiredMemoriesJob())
    ->dailyAt('00:10')
    ->withoutOverlapping();

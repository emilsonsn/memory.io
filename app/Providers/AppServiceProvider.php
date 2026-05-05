<?php

namespace App\Providers;

use App\Events\CategoryExportRequested;
use App\Events\MemoryDeletedByScheduler;
use App\Events\MemoryDeletionReminderTriggered;
use App\Listeners\CreateMemoryDeletedNotification;
use App\Listeners\CreateMemoryDeletionReminderNotification;
use App\Listeners\ProcessCategoryExportRequested;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Memory;
use App\Models\Notification;
use App\Policies\CategoryPolicy;
use App\Policies\FavoritePolicy;
use App\Policies\MemoryPolicy;
use App\Policies\NotificationPolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(CategoryExportRequested::class, ProcessCategoryExportRequested::class);
        Event::listen(MemoryDeletedByScheduler::class, CreateMemoryDeletedNotification::class);
        Event::listen(MemoryDeletionReminderTriggered::class, CreateMemoryDeletionReminderNotification::class);

        Gate::policy(Memory::class, MemoryPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Favorite::class, FavoritePolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                        ->as('bearerAuth')
                        ->setDescription('Use a bearer token to authenticate requests.'),
                );
            });
    }
}

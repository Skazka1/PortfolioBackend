<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Observers\ProjectObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Route;
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
        User::observe(UserObserver::class);
        Project::observe(ProjectObserver::class);

        Route::bind('student', function (string $value) {
            return User::query()
                ->whereKey($value)
                ->where('role', UserRole::Student)
                ->firstOrFail();
        });
    }
}

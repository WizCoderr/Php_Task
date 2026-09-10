<?php

namespace App\Providers;

use App\Services\TaskService;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    // Used to register the TaskService singleton in the application container.
    public function register(): void
    {
        $this->app->singleton(TaskService::class);
    }

    // Called after register is called.
    public function boot(): void
    {
        //
    }
}

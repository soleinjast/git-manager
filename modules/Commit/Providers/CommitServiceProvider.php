<?php

namespace Modules\Commit\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Commit\database\repository\CommitRepository;
use Modules\Commit\database\repository\CommitRepositoryInterface;

class CommitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->bind(CommitRepositoryInterface::class, CommitRepository::class);
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

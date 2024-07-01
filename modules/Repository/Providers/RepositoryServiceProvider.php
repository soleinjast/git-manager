<?php

namespace Modules\Repository\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Repository\database\repository\BranchRepository;
use Modules\Repository\database\repository\BranchRepositoryInterface;
use Modules\Repository\database\repository\RepositoryRepository;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\Console\UpdateRepositoryChanges;
use Modules\Repository\src\DTOs\CreateRepositoryDetails;
use Modules\Repository\src\DTOs\CreateRepositoryDetailsInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->bind(RepositoryRepositoryInterface::class, RepositoryRepository::class);
        $this->app->bind(CreateRepositoryDetailsInterface::class, CreateRepositoryDetails::class);
        $this->app->bind(BranchRepositoryInterface::class, BranchRepository::class);
        $this->app->register(EventServiceProvider::class);
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->commands([
            UpdateRepositoryChanges::class,
        ]);
    }
}

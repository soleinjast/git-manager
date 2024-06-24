<?php

namespace Modules\User\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\User\database\repository\UserRepository;
use Modules\User\database\repository\UserRepositoryInterface;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->register(EventServiceProvider::class);
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

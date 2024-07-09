<?php

namespace Modules\Token\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Token\src\DTOs\CreateTokenDetails;
use Modules\Token\src\DTOs\CreateTokenDetailsInterface;

class GithubTokenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->bind(CreateTokenDetailsInterface::class, CreateTokenDetails::class);

    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../Ui/resources/views', 'TokenApp');
    }
}

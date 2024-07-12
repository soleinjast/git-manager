<?php

namespace Modules\User\Providers;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    public function boot(): void
    {
        $this->routes(function (){
            Route::middleware('api')
                ->prefix('user')
                ->name('user.')
                ->group(__DIR__ . '/../src/Http/routes/user.php');
        });
    }
}

<?php

namespace Modules\Commit\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseRouteServiceProvider;
class RouteServiceProvider extends BaseRouteServiceProvider
{
    public function boot(): void
    {
        $this->routes(function (){
            Route::middleware('api')
                ->prefix('repository')
                ->name('commit.')
                ->group(__DIR__ . '/../src/Http/routes/commit.php');
        });
    }
}

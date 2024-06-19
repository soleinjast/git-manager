<?php

namespace Modules\Repository\Providers;


use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    public function boot(): void
    {
        $this->routes(function (){
            Route::middleware('api')
                ->prefix('repository')
                ->name('repository.')
                ->group(__DIR__ . '/../src/Http/routes/repository.php');
        });
    }
}

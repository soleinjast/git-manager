<?php

namespace Modules\Token\Providers;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseServiceProvider
{
    public function boot() : void
    {
        $this->routes(function (){
            Route::middleware('api')
                ->prefix('token')
                ->name('token.')
                ->group(__DIR__ . '/../src/Http/routes/token.php');
        });
    }
}



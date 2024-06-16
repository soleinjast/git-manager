<?php

use App\Providers\AppServiceProvider;
use Modules\Token\Providers\GithubTokenServiceProvider;

return [
    AppServiceProvider::class,
    GithubTokenServiceProvider::class,
];

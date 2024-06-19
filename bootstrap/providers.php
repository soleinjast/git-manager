<?php

use App\Providers\AppServiceProvider;
use Modules\Repository\Providers\RepositoryServiceProvider;
use Modules\Token\Providers\GithubTokenServiceProvider;

return [
    AppServiceProvider::class,
    GithubTokenServiceProvider::class,
    RepositoryServiceProvider::class
];

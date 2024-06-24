<?php

use App\Providers\AppServiceProvider;
use Modules\Commit\Providers\CommitServiceProvider;
use Modules\Repository\Providers\RepositoryServiceProvider;
use Modules\Token\Providers\GithubTokenServiceProvider;
use Modules\User\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    GithubTokenServiceProvider::class,
    RepositoryServiceProvider::class,
    CommitServiceProvider::class,
    UserServiceProvider::class
];

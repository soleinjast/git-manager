<?php

namespace App\helpers;


use App\Services\GithubService;
use Illuminate\Support\Facades\Route;

if (! function_exists('github_service')) {
    function github_service(string $token, string $owner, string $name): GithubService
    {
        $gitHubService = new GithubService();
        $gitHubService->setModel($token, $owner, $name);
        return $gitHubService;
    }
}

if (! function_exists('isActiveRoute')) {
    function isActiveRoute($route, $output = 'active') {
        return Route::currentRouteName() == $route ? $output : '';
    }
}

if (! function_exists('areActiveRoutes')) {
    function areActiveRoutes(array $routes, $output = 'active open') {
        if (in_array(Route::currentRouteName(), $routes)) {
            return $output;
        }
        return '';
    }
}

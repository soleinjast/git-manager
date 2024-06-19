<?php

namespace App\helpers;


use App\Services\GithubService;

if (! function_exists('github_service')) {
    function github_service(string $token, string $owner, string $name): GithubService
    {
        $gitHubService = new GithubService();
        $gitHubService->setModel($token, $owner, $name);
        return $gitHubService;
    }
}

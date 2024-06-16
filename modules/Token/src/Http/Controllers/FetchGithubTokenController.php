<?php

namespace Modules\Token\src\Http\Controllers;

use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Modules\Token\database\repository\TokenRepository;

class FetchGithubTokenController
{
    use ApiResponse;
    public function __construct(protected TokenRepository $tokenRepository)
    {

    }

    public function __invoke(): JsonResponse
    {
        try {
            return $this->successResponse($this->tokenRepository->fetch());
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), [], 500);
        }
    }
}

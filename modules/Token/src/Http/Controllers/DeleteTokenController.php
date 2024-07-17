<?php

namespace Modules\Token\src\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Token\database\repository\TokenRepositoryInterface;
use Modules\Token\src\Exceptions\TokenDeletionFailedException;

class DeleteTokenController
{
    use ApiResponse;
    public function __construct(protected TokenRepositoryInterface $tokenRepository)
    {

    }
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $this->tokenRepository->delete($request->tokenId);
        } catch (TokenDeletionFailedException $exception) {
            report($exception);
            return $this->errorResponse();
        }
        return $this->successResponse([]);
    }
}

<?php

namespace Modules\Token\src\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Token\database\repository\TokenRepository;
use Modules\Token\src\DTOs\CreateTokenDetails;
use Modules\Token\src\DTOs\TokenDto;
use Modules\Token\src\Exceptions\TokenCreationFailedException;

class CreateGithubTokenController
{
    use ApiResponse;
    public function __construct(protected TokenRepository $tokenRepository)
    {

    }
    public function __invoke() : JsonResponse
    {
        try {
        $createTokenDetails = new CreateTokenDetails(
            token: request('token'),
            login_name: request('login_name'),
            githubId: request('githubId')
        );
            $tokenDto = $this->tokenRepository->create(createTokenDetails: $createTokenDetails);
            return $this->successResponse(data: $tokenDto->toArray());
        } catch (TokenCreationFailedException $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }
}

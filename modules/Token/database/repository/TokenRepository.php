<?php

namespace Modules\Token\database\repository;

use Exception;
use Modules\Token\src\DTOs\CreateTokenDetails;
use Modules\Token\src\DTOs\CreateTokenDetailsInterface;
use Modules\Token\src\DTOs\TokenDto;
use Modules\Token\src\Enumerations\GithubTokenApiResponses;
use Modules\Token\src\Exceptions\TokenCreationFailedException;
use Modules\Token\src\Exceptions\TokenDeletionFailedException;
use Modules\Token\src\Models\GithubToken;

class TokenRepository implements TokenRepositoryInterface
{
    /**
     * @throws TokenCreationFailedException
     */
    public function create(CreateTokenDetailsInterface $createTokenDetails): TokenDto
    {
        try {
            $githubToken =  GithubToken::query()->create([
                'token' => $createTokenDetails->token,
                'login_name' => $createTokenDetails->login_name,
                'githubId' => $createTokenDetails->githubId
            ]);
            return TokenDto::fromEloquent($githubToken);
        } catch (Exception $exception) {
            report($exception);
            throw new TokenCreationFailedException(GithubTokenApiResponses::TokenCreationFailed);
        }
    }

    /**
     * @throws Exception
     */
    public function fetch() : array {
        try {
            $tokens = GithubToken::query()->orderBy('created_at', 'DESC')->get();
            return $tokens->toArray();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @throws TokenDeletionFailedException
     */
    public function delete(int $id): bool
    {
        try {
            $token = GithubToken::query()->find($id);
            return $token->delete();
        } catch (Exception $exception) {
            report($exception);
            throw new TokenDeletionFailedException("Token deletion failed!");
        }
    }
}

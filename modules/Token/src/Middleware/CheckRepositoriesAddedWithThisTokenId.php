<?php

namespace Modules\Token\src\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Modules\Token\src\Models\GithubToken;

class CheckRepositoriesAddedWithThisTokenId
{
    use ApiResponse;
    public function handle(Request $request, Closure $next)
    {
        $tokenId = $request->route('tokenId');
        $token = GithubToken::query()->find($tokenId);
        if ($token->repositories->count() > 0) {
            return $this->errorResponse('Token has repositories added to it. Please remove them first', [], 400);
        }
        return $next($request);
    }
}

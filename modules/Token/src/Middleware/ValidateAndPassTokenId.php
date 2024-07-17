<?php

namespace Modules\Token\src\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Modules\Token\src\Models\GithubToken;

class ValidateAndPassTokenId
{
    use ApiResponse;
    public function handle(Request $request, Closure $next)
    {
         $tokenId = $request->route('tokenId');
         if (GithubToken::query()->find($tokenId)){
             $request->merge(['tokenId' => $tokenId]);
             return $next($request);
         }else{
                return $this->errorResponse('Token not found', [], 422);
         }

    }
}

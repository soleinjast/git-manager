<?php

namespace Modules\Repository\src\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Repository\src\DTOs\CreateRepositoryDetails;
use Modules\Repository\src\Events\RepositoriesCreationOnGithubInitiated;
use Modules\Token\src\Models\GithubToken;


class AutoRepositoryController
{
    use ApiResponse;
    public function __construct(protected Dispatcher $events)
    {

    }
    public function __invoke(Request $request): JsonResponse
    {
        $groupCount = $request->input('group_count');
        $membersPerGroup = $request->input('members_per_group');
        $members = $request->input('members');
        $organization = $request->input('organization');
        $deadline = $request->input('deadline');
        $tokenId = $request->input('token_id');
        $githubToken = GithubToken::query()->find($tokenId);
        $token = $githubToken->token;
        $groups = array_chunk($members, $membersPerGroup);
        foreach ($groups as $group){
            $baseRepositoryName = "{$organization}-group";
            $repositoryName = $this->generateUniqueRepositoryName($baseRepositoryName);
            $repositoryDetails = new CreateRepositoryDetails(
                $tokenId,
                $organization,
                $repositoryName,
                $deadline
            );
            $this->events->dispatch(new RepositoriesCreationOnGithubInitiated($repositoryDetails, $group, $githubToken));
        }
        return $this->successResponse("Repositories and collaborators creation process initiated successfully.");
    }

    private function generateUniqueRepositoryName(string $baseRepositoryName) : string
    {
        return $baseRepositoryName . '-' . Str::uuid();
    }
}

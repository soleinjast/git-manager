<?php

namespace Modules\Repository\src\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Repository\src\DTOs\CreateRepositoryDetails;
use Modules\Token\src\Models\GithubToken;

class RepositoriesCreationOnGithubCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public function __construct(public CreateRepositoryDetails $repositoryDetails,
                                public array $members,
                                public GithubToken $githubToken)
    {

    }
}

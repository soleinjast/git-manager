<?php

namespace Modules\Repository\src\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Events\RepositoriesCreationOnGithubCreated;
use Modules\Repository\src\Exceptions\RepositoryCreationFailedException;
use Modules\User\database\repository\UserRepositoryInterface;
use Modules\User\src\DTOs\UserCreateDetails;

class StoreRepositoryAndCollaboratorsOnLocal implements ShouldQueue
{
    use InteractsWithQueue;
    public function __construct(protected RepositoryRepositoryInterface $repositoryRepository,
                                protected UserRepositoryInterface $userRepository)
    {

    }

    public function handle(RepositoriesCreationOnGithubCreated $event) : void
    {
        try {
            $repositoryDto = $this->repositoryRepository->create($event->repositoryDetails);
        } catch (RepositoryCreationFailedException $e) {
            report($e);
            return;
        }
        foreach ($event->members as $member){
            /** @var RepositoryDto $repositoryDto */
            $userCreationDetails = new UserCreateDetails(repositoryId: $repositoryDto->id,
                login_name: $member['github_username'],
                name: $member['name'] ?? '',
                git_id: $member['git_id'],
                avatar_url: $member['avatar_url'],
                university_username: $member['university_username'],
                status: 'pending'
            );
            $this->userRepository->updateOrCreate($userCreationDetails);
        }
    }
}

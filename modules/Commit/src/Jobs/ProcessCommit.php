<?php

namespace Modules\Commit\src\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Commit\database\repository\CommitRepositoryInterface;
use Modules\Commit\src\DTOs\CreateCommitDetails;
use Modules\Commit\src\Events\CommitCreated;
use Modules\Commit\src\Exceptions\FailedToCheckIfCommitExistsException;
use Modules\Commit\src\Models\Commit;
use Modules\Repository\src\DTOs\RepositoryDto;


class ProcessCommit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public RepositoryDto $repository, public array $commitData)
    {

    }

    public function handle(CommitRepositoryInterface $commitRepository): void
    {
        try {
            if ($commitRepository->existsByShaAndRepositoryId($this->commitData['sha'], $this->repository->id)) {
                return;
            }
        } catch (FailedToCheckIfCommitExistsException $e) {
            report($e);
            return;
        }
        $createCommitDetails = new CreateCommitDetails(
            repositoryId: $this->repository->id,
            sha: $this->commitData['sha'],
            message: $this->commitData['commit']['message'],
            author: $this->commitData['commit']['author']['name'],
            date: $this->commitData['commit']['author']['date'],
            is_first_commit: !$this->commitData['parents'],
            author_git_id: $this->commitData['author']['id'] ?? null
        );
        $commitDto = $commitRepository->create($createCommitDetails);
        event(new CommitCreated($this->repository, $commitDto));
    }

}

<?php

namespace Modules\Commit\src\Jobs;

use App\Services\GithubService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Commit\database\repository\CommitFileRepositoryInterface;
use Modules\Commit\src\DTOs\CreateCommitFileDetails;

class ProcessCommitFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $fileData, public int $commitId, public bool $is_first_commit)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(CommitFileRepositoryInterface $commitFileRepository, GithubService $githubService): void
    {
        $meaningful = !empty($this->fileData['patch'])
            && $githubService->isMeaningfulPatch($this->fileData['patch'])
            && !$this->is_first_commit;;
        $createCommitFileDetails = new CreateCommitFileDetails(
            commit_id: $this->commitId,
            filename: $this->fileData['filename'],
            status: $this->fileData['status'],
            changes: $this->fileData['patch'] ?? '',
            meaningful: $meaningful
        );
        $commitFileRepository->updateOrCreate($createCommitFileDetails);
    }
}

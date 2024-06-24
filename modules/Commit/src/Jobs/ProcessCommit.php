<?php

namespace Modules\Commit\src\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Commit\database\repository\CommitRepositoryInterface;
use Modules\Commit\src\DTOs\CreateCommitDetails;


class ProcessCommit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public CreateCommitDetails $createCommitDetails)
    {

    }

    public function handle(CommitRepositoryInterface $commitRepository): void
    {
        $commitRepository->updateOrCreate($this->createCommitDetails);
    }
}

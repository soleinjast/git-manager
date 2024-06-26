<?php

namespace Modules\Commit\src\Events;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Commit\src\DTOs\CommitDto;
use Modules\Repository\src\DTOs\RepositoryDto;

class CommitCreated
{
    use Dispatchable, SerializesModels;
    public function __construct(public RepositoryDto $repositoryDto, public CommitDto $commit)
    {

    }
}

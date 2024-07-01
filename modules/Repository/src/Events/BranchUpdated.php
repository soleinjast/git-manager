<?php

namespace Modules\Repository\src\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Repository\src\DTOs\BranchDto;
use Modules\Repository\src\DTOs\RepositoryDto;

class BranchUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public function __construct(public BranchDto $branch, public RepositoryDto $repository)
    {

    }
}

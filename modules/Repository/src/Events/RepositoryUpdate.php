<?php

namespace Modules\Repository\src\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Repository\src\DTOs\RepositoryDto;

class RepositoryUpdate
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public function __construct(public RepositoryDto $repository)
    {

    }
}

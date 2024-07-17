<?php

namespace Modules\Commit\src\Events;

use Modules\Commit\src\Models\Commit;

/**
 * @property $commit
 */
class CommitDeleted
{
    public function __construct(public Commit $commit)
    {
    }
}

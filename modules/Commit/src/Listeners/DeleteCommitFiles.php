<?php

namespace Modules\Commit\src\Listeners;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Commit\src\Events\CommitDeleted;
use Modules\Commit\src\Models\CommitFile;

class DeleteCommitFiles implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CommitDeleted $event) : void
    {
        $commit = $event->commit;
        try {
            CommitFile::query()->where('commit_id', $commit->id)->chunkById(100, function ($commitFiles) {
                foreach ($commitFiles as $commitFile) {
                    $commitFile->delete();
                }
            });
        } catch (Exception $e) {
            report($e);
        }
    }
}

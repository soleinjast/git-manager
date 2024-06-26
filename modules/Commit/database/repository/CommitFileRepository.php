<?php

namespace Modules\Commit\database\repository;

use Modules\Commit\src\DTOs\CreateCommitFileDetails;
use Modules\Commit\src\Models\CommitFile;

class CommitFileRepository implements CommitFileRepositoryInterface
{

    public function updateOrCreate(CreateCommitFileDetails $commitFileDetails): void
    {
        try {
            CommitFile::query()->updateOrCreate([
                'commit_id' => $commitFileDetails->commit_id,
                'filename' => $commitFileDetails->filename,
                'status' => $commitFileDetails->status,
                'changes' => $commitFileDetails->changes,
                'meaningful' => $commitFileDetails->meaningful,
            ]);
        }catch (\Exception $exception){
            report($exception);
        }
    }
}

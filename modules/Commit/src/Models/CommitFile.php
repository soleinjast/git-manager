<?php

namespace Modules\Commit\src\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Commit\database\factories\CommitFileFactory;

class CommitFile extends Model
{
    use HasFactory;
    protected $fillable = [
        'commit_id',
        'filename',
        'status',
        'changes',
        'meaningful'
    ];

    protected static function newFactory(): CommitFileFactory
    {
        return new CommitFileFactory();
    }
}

<?php

namespace Modules\Commit\src\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

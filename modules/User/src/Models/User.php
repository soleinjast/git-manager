<?php

namespace Modules\User\src\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Commit\src\Models\Commit;
use Modules\User\database\factories\UserFactory;

/**
 * @property int $id
 * @property int $repository_id
 * @property string $login_name
 * @property string $name
 * @property string $git_id
 * @property string $avatar_url
 */
class User extends Model
{
    use HasFactory;
    protected $fillable = [
        'repository_id',
        'login_name',
        'name',
        'git_id',
        'avatar_url'
    ];

    protected static function newFactory(): UserFactory
    {
        return new UserFactory();
    }

    protected $appends = ['commit_count',
        'meaningful_commit_files_count',
        'not_meaningful_commit_files_count',
        'github_url'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H-i-s',
        'updated_at' => 'datetime:Y-m-d H-i-s',
    ];

    public function getGithubUrlAttribute(): string
    {
        return "https://github.com/{$this->login_name}";
    }

    public function commits(): HasMany
    {
        return $this->hasMany(Commit::class, 'author_git_id', 'git_id');
    }

    public function getCommitCountAttribute(): int
    {
        return $this->commits()->where('repository_id', $this->repository_id)->count();
    }
    public function getMeaningfulCommitFilesCountAttribute(): int
    {
        return $this->commits()->where('repository_id', $this->repository_id)->with('commitFiles')->get()->sum(function ($commit) {
            return $commit->commitFiles->where('meaningful', true)->count();
        });
    }
    public function getNotMeaningfulCommitFilesCountAttribute(): int
    {
        return $this->commits()->where('repository_id', $this->repository_id)->with('commitFiles')->get()->sum(function ($commit) {
            return $commit->commitFiles->where('meaningful', false)->count();
        });
    }

}

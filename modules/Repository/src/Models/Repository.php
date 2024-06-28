<?php

namespace Modules\Repository\src\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Commit\src\Models\Commit;
use Modules\Repository\database\factories\RepositoryFactory;
use Modules\Token\src\Models\GithubToken;
use Modules\User\src\Models\User;

/**
 * @property mixed $id
 * @property mixed $owner
 * @property mixed $name
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property mixed $github_token_id
 * @property mixed $deadline
 * @property mixed $github_url
 * @property mixed $commits_dashboard_url
 * @property mixed $total_commit_files_count
 * @property mixed $meaningful_commit_files_count
 * @property mixed $not_meaningful_commit_files_count
 * @property mixed $last_commit
 * @property mixed $first_commit
 * @property mixed $commits
 * @property mixed $collaborators
 * @property mixed $token
 * @method static searchByName(string|null $search)
 * @method static searchByOwner(string|null $search)
 */
class Repository extends Model
{
    use HasFactory;
    protected $fillable = ['owner', 'name', 'github_token_id', 'deadline'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H-i-s',
        'updated_at' => 'datetime:Y-m-d H-i-s',
        'deadline' => 'datetime:Y-m-d H-i-s',
    ];

    protected $appends = [
        'github_url',
        'commits_dashboard_url',
        'total_commit_files_count',
        'meaningful_commit_files_count',
        'not_meaningful_commit_files_count',
        'last_commit',
        'first_commit',
    ];

    protected static function newFactory(): RepositoryFactory
    {
        return new RepositoryFactory();
    }

    // Define the scope for searching by name
    public function scopeSearchByName(Builder $query, ?string $searchName): Builder
    {
        if ($searchName) {
            return $query->where('name', 'like', '%' . $searchName . '%');
        }
        return $query;
    }

    // Define the scope for searching by owner
    public function scopeSearchByOwner(Builder $query, ?string $searchOwner): Builder
    {
        if ($searchOwner) {
            return $query->where('owner', 'like', '%' . $searchOwner . '%');
        }
        return $query;
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(GithubToken::class, 'github_token_id');
    }

    public function commits(): HasMany
    {
        return $this->hasMany(Commit::class);
    }
    public function getGithubUrlAttribute(): string
    {
        return "https://github.com/{$this->owner}/{$this->name}";
    }

    public function getLastCommitAttribute(): ?string
    {
        $lastCommit = $this->commits()->latest('date')->first();
        return $lastCommit ? Carbon::parse($lastCommit->date)->toDateTimeString() : null;
    }

    public function getFirstCommitAttribute(): ?string
    {
        $firstCommit = $this->commits()->oldest('date')->first();
        return $firstCommit ? Carbon::parse($firstCommit->date)->toDateTimeString() : null;
    }

    public function getMeaningfulCommitFilesCountAttribute()
    {
        return $this->commits()->with('commitFiles')->get()->sum(function ($commit) {
            return $commit->commitFiles->where('meaningful', true)->count();
        });
    }

    public function getNotMeaningfulCommitFilesCountAttribute()
    {
        return $this->commits()->with('commitFiles')->get()->sum(function ($commit) {
            return $commit->commitFiles->where('meaningful', false)->count();
        });
    }
    public function getTotalCommitFilesCountAttribute()
    {
        return $this->commits()->withCount('commitFiles')->get()->sum('commit_files_count');
    }
    public function collaborators(): HasMany
    {
        return $this->hasMany(User::class);
    }

}

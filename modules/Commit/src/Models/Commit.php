<?php

namespace Modules\Commit\src\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Commit\database\factories\CommitFactory;
use Modules\Repository\src\Models\Repository;
use Modules\User\src\Models\User;

/**
 * @property mixed $id
 * @property mixed $repository_id
 * @property mixed $sha
 * @property mixed $message
 * @property mixed $author
 * @property mixed $date
 * @property mixed $author_git_id
 * @property mixed $is_first_commit
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property mixed $has_non_meaningful_files
 * @property mixed $user
 * @property mixed $commits_files_dashboard_url
 * @property mixed $github_url
 */
class Commit extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'sha',
        'message',
        'author',
        'date',
        'author_git_id',
        'is_first_commit',
        'author_git_id'
    ];

    protected $casts = [
        'date' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $appends = ['has_non_meaningful_files', 'commits_files_dashboard_url', 'github_url'];

    protected static function newFactory(): CommitFactory
    {
        return new CommitFactory();
    }
    public function commitFiles(): HasMany
    {
        return $this->hasMany(CommitFile::class);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function scopeFilterByAuthor(Builder $query, ?string $author): Builder
    {
        return $author ? $query->where('author_git_id', $author) : $query;
    }

    public function scopeFilterByStartDate(Builder $query, ?string $startDate): Builder
    {
        return $startDate ? $query->whereDate('date', '>=', $startDate) : $query;
    }

    public function scopeFilterByEndDate(Builder $query, ?string $endDate): Builder
    {
        return $endDate ? $query->whereDate('date', '<=', $endDate) : $query;
    }

    public function getHasNonMeaningfulFilesAttribute(): bool
    {
        return $this->commitFiles()->where('meaningful', false)->exists();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_git_id', 'git_id')
            ->where('repository_id', $this->repository_id)
            ->withDefault([
            'name' => 'Unknown',
            'login_name' => 'Unknown',
        ]);
    }
    public function getCommitsFilesDashboardUrlAttribute(): string
    {
        return route('commit.commit-detail-view', [$this->repository_id, $this->sha]);
    }

    public function getGithubUrlAttribute(): string
    {
        return $this->repository->getGithubUrlAttribute().'/commit/'.$this->sha;
    }
}

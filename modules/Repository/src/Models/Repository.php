<?php

namespace Modules\Repository\src\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Repository\database\factories\RepositoryFactory;
use Modules\Token\src\Models\GithubToken;

/**
 * @property mixed $id
 * @property mixed $owner
 * @property mixed $name
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property mixed $github_token_id
 * @property mixed $deadline

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

}

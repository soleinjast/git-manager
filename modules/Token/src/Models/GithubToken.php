<?php

namespace Modules\Token\src\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Repository\src\Models\Repository;
use Modules\Token\database\factories\GithubTokenFactory;

/**
 * @property mixed $id
 * @property string $token
 * @property mixed $login_name
 * @property mixed $githubId
 */
class GithubToken extends Model
{
    use HasFactory;
    protected $fillable = ['token', 'login_name', 'githubId'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H-i-s',
        'updated_at' => 'datetime:Y-m-d H-i-s'
    ];

    protected static function newFactory() : GithubTokenFactory
    {
        return new GithubTokenFactory();
    }
    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }
}

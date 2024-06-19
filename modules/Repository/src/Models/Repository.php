<?php

namespace Modules\Repository\src\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Repository\database\factories\RepositoryFactory;

/**
 * @property mixed $id
 * @property mixed $owner
 * @property mixed $name
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property mixed $github_token_id
 * @property mixed $deadline
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

}

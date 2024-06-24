<?php

namespace Modules\User\src\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H-i-s',
        'updated_at' => 'datetime:Y-m-d H-i-s',
    ];
}

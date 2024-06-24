<?php

namespace Modules\Repository\src\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Repository\database\factories\BranchFactory;


/**
 * @property int $repository_id
 * @property string $name
 * @property int $id
 */
class Branch extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'repository_id'];

    protected $casts = [
        'repository_id' => 'integer',
        'created_at' => 'datetime:Y-m-d H-i-s',
        'updated_at' => 'datetime:Y-m-d H-i-s',
        'deadline' => 'datetime:Y-m-d H-i-s',
    ];

    protected static function newFactory(): BranchFactory
    {
        return new BranchFactory();
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

}

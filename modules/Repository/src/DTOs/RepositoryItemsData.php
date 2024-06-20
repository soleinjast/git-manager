<?php

namespace Modules\Repository\src\DTOs;

use Illuminate\Support\Collection;

class RepositoryItemsData
{
    public static function fromRepositoryEloquentCollection(Collection $repositories) : array
    {
        $data = [];
        foreach ($repositories as $repository){
            $data[] = RepositoryDto::fromEloquent($repository)->toArray();
        }
        return $data;
    }
}

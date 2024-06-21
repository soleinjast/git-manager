<?php

namespace Modules\Repository\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Repository\src\Models\Branch;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'repository_id' => 1,
            'name' => $this->faker->slug,
        ];
    }
}

<?php

namespace Modules\Repository\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Repository\src\Models\Repository;

class RepositoryFactory extends Factory
{
    protected $model = Repository::class;

    public function definition(): array
    {
        return [
            'owner' => $this->faker->userName,
            'name' => $this->faker->slug,
            'github_token_id' => 1
        ];
    }
}

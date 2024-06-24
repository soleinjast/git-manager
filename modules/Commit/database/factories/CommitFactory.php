<?php

namespace Modules\Commit\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Commit\src\Models\Commit;

class CommitFactory extends Factory
{
    protected $model = Commit::class;

    public function definition(): array
    {
        return [
            'repository_id' => 1,
            'sha' => fake()->unique()->sha1,
            'message' => fake()->sentence,
            'author' => fake()->name,
            'date' => fake()->dateTime,
            'author_git_id' => fake()->unique()->numberBetween(1, 100),
            'is_first_commit' => false,
        ];
    }
}

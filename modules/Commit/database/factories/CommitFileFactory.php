<?php

namespace Modules\Commit\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Commit\src\Models\CommitFile;

class CommitFileFactory extends Factory
{
    protected $model = CommitFile::class;

    public function definition(): array
    {
        return [
            'commit_id' => 1,
            'filename' => $this->faker->word . '.txt',
            'status' => $this->faker->randomElement(['added', 'modified', 'removed', 'renamed']),
            'changes' => $this->faker->paragraph,
            'meaningful' => $this->faker->boolean,
        ];
    }
}

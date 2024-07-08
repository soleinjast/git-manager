<?php

namespace Modules\User\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\User\src\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;
    public function definition(): array
    {
        return [
            'repository_id' => $this->faker->randomNumber(),
            'login_name' => $this->faker->userName,
            'name' => $this->faker->name,
            'git_id' => $this->faker->randomNumber(),
            'avatar_url' => $this->faker->imageUrl(),
            'university_username' => $this->faker->userName,
            'status' => $this->faker->randomElement(['approved', 'pending']),
        ];
    }
}

<?php

namespace Modules\Token\database\factories;

use Modules\Token\src\Models\GithubToken;
use Illuminate\Database\Eloquent\Factories\Factory;
class GithubTokenFactory extends Factory
{
    protected $model = GithubToken::class;

    public function definition() : array
    {
        return [
            'token' => $this->faker->unique()->sha256,
            'login_name' => $this->faker->userName,
            'githubId' => $this->faker->unique()->randomNumber(),

        ];
    }

    public function accessible() : Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'token' => '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',
                'login_name' => 'soleinjast',
                'githubId' => '117115652'
            ];
        });
    }
}

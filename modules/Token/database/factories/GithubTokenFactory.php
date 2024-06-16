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

<?php

namespace Modules\User\tests\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Modules\User\src\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_user_using_factory()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $user = User::factory()->create([
            'repository_id' => $repository->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'repository_id' => $user->repository_id,
            'login_name' => $user->login_name,
            'name' => $user->name,
            'git_id' => $user->git_id,
            'avatar_url' => $user->avatar_url,
        ]);
    }
}

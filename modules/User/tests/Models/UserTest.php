<?php

namespace Modules\User\tests\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commit\src\Models\Commit;
use Modules\Commit\src\Models\CommitFile;
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

    public function test_casts()
    {
        $user = new User();
        $this->assertEquals(['created_at' => 'datetime:Y-m-d H-i-s', 'updated_at' => 'datetime:Y-m-d H-i-s','id' => 'int'], $user->getCasts());
    }

    public function test_fillable()
    {
        $user = new User();
        $this->assertEquals(['repository_id', 'login_name', 'university_username','name', 'status', 'git_id', 'avatar_url'], $user->getFillable());
    }

    public function test_github_url_attribute()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $user = User::factory()->make([
            'repository_id' => $repository->id,
            'login_name' => 'test-login-name',
        ]);
        $this->assertEquals("https://github.com/test-login-name", $user->github_url);
    }

    public function test_it_can_count_commits_for_specific_repository()
    {
        $githubToken = GithubToken::factory()->create();
        $repositories = Repository::factory()->count(2)->create();
        $user = User::factory()->create(['repository_id' => $repositories[0]->id, 'git_id' => 123]);
        Commit::factory()->create(['repository_id' => $repositories[0]->id, 'author_git_id' => 123]);
        Commit::factory()->create(['repository_id' => $repositories[0]->id, 'author_git_id' => 123]);
        Commit::factory()->create(['repository_id' => $repositories[1]->id, 'author_git_id' => 123]);

        $this->assertEquals(2, $user->commit_count);
    }

    public function test_it_can_count_meaningful_commit_files()
    {
        $githubToken = GithubToken::factory()->create();
        $repositories = Repository::factory()->count(2)->create();
        $user = User::factory()->create(['repository_id' => $repositories[0]->id, 'git_id' => 123]);

        $commit1 = Commit::factory()->create(['repository_id' => $repositories[0]->id, 'author_git_id' => 123]);
        $commit2 = Commit::factory()->create(['repository_id' => $repositories[0]->id, 'author_git_id' => 123]);

        CommitFile::factory()->create(['commit_id' => $commit1->id, 'meaningful' => true]);
        CommitFile::factory()->create(['commit_id' => $commit1->id, 'meaningful' => false]);
        CommitFile::factory()->create(['commit_id' => $commit2->id, 'meaningful' => true]);
        $this->assertEquals(2, $user->meaningful_commit_files_count);
    }

    public function test_it_can_count_not_meaningful_commit_files()
    {
        $githubToken = GithubToken::factory()->create();
        $repositories = Repository::factory()->count(2)->create();
        $user = User::factory()->create(['repository_id' => 1, 'git_id' => 123]);
        $commit1 = Commit::factory()->create(['repository_id' => 1, 'author_git_id' => 123]);
        $commit2 = Commit::factory()->create(['repository_id' => 1, 'author_git_id' => 123]);

        CommitFile::factory()->create(['commit_id' => $commit1->id, 'meaningful' => false]);
        CommitFile::factory()->create(['commit_id' => $commit1->id, 'meaningful' => false]);
        CommitFile::factory()->create(['commit_id' => $commit2->id, 'meaningful' => true]);

        $this->assertEquals(2, $user->not_meaningful_commit_files_count);
    }

}

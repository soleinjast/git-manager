<?php

namespace Modules\Repository\tests\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commit\src\Models\Commit;
use Modules\Commit\src\Models\CommitFile;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Modules\User\src\Models\User;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    use RefreshDatabase;
    public function testFactory()
    {
        $repository = Repository::factory()->make();
        $this->assertIsString($repository->owner);
        $this->assertIsString($repository->name);
    }
    public function testFillable()
    {
        $repository = new Repository();
        $this->assertEquals(['owner', 'name', 'github_token_id', 'deadline'], $repository->getFillable());
    }
    public function testCasts()
    {
        $repository = new Repository();
        $this->assertEquals(['created_at' => 'datetime:Y-m-d H-i-s', 'updated_at' => 'datetime:Y-m-d H-i-s', 'deadline' => 'datetime:Y-m-d H-i-s' ,'id' => 'int'], $repository->getCasts());
    }

    public function test_github_url_is_appended()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
        ]);

        $this->assertEquals('https://github.com/test-owner/test-repo', $repository->github_url);
    }

    public function test_last_commit_is_appended()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $commit = Commit::factory()->create([
            'repository_id' => $repository->id,
            'date' => Carbon::now()->subDay(),
        ]);

        $this->assertEquals(Carbon::parse($commit->date)->toDateTimeString(), $repository->last_commit);
    }
    public function test_first_commit_is_appended()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $commit = Commit::factory()->create([
            'repository_id' => $repository->id,
            'date' => Carbon::now()->subDays(2),
        ]);

        $this->assertEquals(Carbon::parse($commit->date)->toDateTimeString(), $repository->first_commit);
    }
    public function test_meaningful_commit_files_count_is_appended()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $commit = Commit::factory()->create(['repository_id' => $repository->id]);

        CommitFile::factory()->count(3)->create([
            'commit_id' => $commit->id,
            'meaningful' => true,
        ]);

        CommitFile::factory()->count(2)->create([
            'commit_id' => $commit->id,
            'meaningful' => false,
        ]);

        $this->assertEquals(3, $repository->meaningful_commit_files_count);
    }

    public function test_not_meaningful_commit_files_count_is_appended()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $commit = Commit::factory()->create(['repository_id' => $repository->id]);

        CommitFile::factory()->count(3)->create([
            'commit_id' => $commit->id,
            'meaningful' => true,
        ]);

        CommitFile::factory()->count(2)->create([
            'commit_id' => $commit->id,
            'meaningful' => false,
        ]);

        $this->assertEquals(2, $repository->not_meaningful_commit_files_count);
    }

    public function test_total_commit_files_count_is_appended()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $commit = Commit::factory()->create(['repository_id' => $repository->id]);

        CommitFile::factory()->count(5)->create(['commit_id' => $commit->id]);

        $this->assertEquals(5, $repository->total_commit_files_count);
    }

    public function test_collaborators_relationship()
    {
        $githubToken = GithubToken::factory()->create();
        // Create a repository
        $repository = Repository::factory()->create();

        // Create users associated with the repository
        $users = User::factory()->count(3)->create(['repository_id' => $repository->id]);

        // Assert that the collaborators relationship returns the correct users
        $this->assertCount(3, $repository->collaborators);
        $this->assertTrue($repository->collaborators->contains($users[0]));
        $this->assertTrue($repository->collaborators->contains($users[1]));
        $this->assertTrue($repository->collaborators->contains($users[2]));
    }
}

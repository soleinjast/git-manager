<?php

namespace Modules\Commit\tests\Models;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commit\src\Models\Commit;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class CommitModelTest extends TestCase
{
    use RefreshDatabase;
    public function test_commit_factory_creates_valid_commit()
    {
        // Create a repository for the commit to belong to
        $git_hub_token = GithubToken::factory()->create();
        $repository = Repository::factory()->create();

        // Create a commit using the factory
        $commit = Commit::factory()->create([
            'repository_id' => $repository->id,
        ]);

        // Assert the commit was created with the correct attributes
        $this->assertDatabaseHas('commits', [
            'id' => $commit->id,
            'repository_id' => $repository->id,
            'sha' => $commit->sha,
            'message' => $commit->message,
            'author' => $commit->author,
            'date' => $commit->date->format('Y-m-d H:i:s'),
            'author_git_id' => $commit->author_git_id,
            'is_first_commit' => $commit->is_first_commit,
        ]);
    }

    public function test_commit_belongs_to_repository()
    {
        $git_hub_token = GithubToken::factory()->create();
        // Create a repository for the commit to belong to
        $repository = Repository::factory()->create();

        // Create a commit using the factory
        $commit = Commit::factory()->create([
            'repository_id' => $repository->id,
        ]);

        $this->assertInstanceOf(Repository::class, $commit->repository);
        $this->assertEquals($repository->id, $commit->repository->id);
    }

    public function testFillable(){
        $commit = new Commit();
        $this->assertEquals(['repository_id', 'sha', 'message', 'author', 'date', 'author_git_id', 'is_first_commit', 'author_git_id'], $commit->getFillable());
    }
    public function testCast(){
        $commit = new Commit();
        $this->assertEquals(['id' => 'int', 'date' => 'datetime:Y-m-d H:i:s', 'created_at' => 'datetime:Y-m-d H:i:s', 'updated_at' => 'datetime:Y-m-d H:i:s'], $commit->getCasts());
    }
}

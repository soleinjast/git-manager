<?php

namespace Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Repository\src\Models\Branch;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;
    public function testFactory()
    {
        $branch = Branch::factory()->make();
        $this->assertIsString($branch->name);
        $this->assertIsInt($branch->repository_id);
    }

    public function testFillable()
    {
        $branch = new Branch();
        $this->assertEquals(['name', 'repository_id'], $branch->getFillable());
    }

    public function testBranchBelongsToRepository()
    {
        // Create a GithubToken
        $githubToken = GithubToken::factory()->create();
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);

        // Create a Repository
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        // Create a Branch that belongs to the Repository
        $branch = Branch::factory()->create([
            'name' => 'main',
            'repository_id' => $repository->id,
        ]);
        // Assert the relationship
        $this->assertInstanceOf(Repository::class, $branch->repository);
        $this->assertEquals($repository->id, $branch->repository->id);
        $this->assertEquals('test-owner', $branch->repository->owner);
        $this->assertEquals('test-repo', $branch->repository->name);
    }
}

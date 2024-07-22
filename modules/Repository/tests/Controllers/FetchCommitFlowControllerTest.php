<?php

namespace Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Commit\src\Models\Commit;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class FetchCommitFlowControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test fetching commit flow successfully.
     *
     * @return void
     */
    public function test_fetch_commit_flow_success()
    {
        // Create a repository
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();

        // Create commits for the repository
        Commit::factory()->count(3)->create([
            'repository_id' => $repository->id,
            'date' => Carbon::now()->subDays(1)->format('Y-m-d'),
        ]);

        Commit::factory()->count(2)->create([
            'repository_id' => $repository->id,
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        // Make a request to the endpoint
        $response = $this->getJson(route('repository.commit-flow', ['repoId' => $repository->id]));

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                "success" => true,
                "message" => "Operation successful",
                "data" => [
                    Carbon::now()->subDays(1)->format('Y-m-d') => 3,
                    Carbon::now()->format('Y-m-d') => 2,
                ]
            ]);
    }
    public function test_fetch_commit_flow_repository_not_found()
    {
        // Make a request to the endpoint with a non-existing repository ID
        $response = $this->getJson(route('repository.commit-flow', ['repoId' => 999]));

        // Assert the response
        $response->assertStatus(400);
    }

    public function test_fetch_commit_flow_general_exception()
    {
        Schema::drop('repositories');
        $response = $this->getJson(route('repository.commit-flow', ['repoId' => 1]));
        $response->assertStatus(400);
    }
}

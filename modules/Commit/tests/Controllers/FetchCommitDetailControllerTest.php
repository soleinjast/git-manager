<?php

namespace Modules\Commit\tests\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Commit\database\repository\CommitRepositoryInterface;
use Modules\Commit\src\Exceptions\FailedToFetchCommitWithCommitFiles;
use Modules\Commit\src\Http\Controllers\FetchCommitDetailController;
use Modules\Commit\src\Middleware\CheckIfCommitShaIsValid;
use Modules\Commit\src\Models\Commit;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class FetchCommitDetailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_commit_detail_with_valid_sha()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $commit = Commit::factory()->create(['repository_id' => $repository->id]);

        // Define the route with middleware in the test method
        Route::middleware([CheckIfCommitShaIsValid::class])
            ->get('/{repoId}/commits/{sha}/fetch', [FetchCommitDetailController::class, '__invoke']);

        $response = $this->get("/{$repository->id}/commits/{$commit->sha}/fetch");

        $response->assertStatus(200);
    }

    public function test_fetch_commit_detail_with_invalid_sha()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();

        // Define the route with middleware in the test method
        Route::middleware([CheckIfCommitShaIsValid::class])
            ->get('/{repoId}/commits/{sha}/fetch', [FetchCommitDetailController::class, '__invoke']);

        $response = $this->get("/{$repository->id}/commits/invalid_sha/fetch");

        $response->assertStatus(400);
    }

    public function test_fetch_commit_detail_successful()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $commit = Commit::factory()->create([
            'repository_id' => $repository->id,
            'sha' => 'valid_sha'
        ]);

        $response = $this->getJson(route('commit.fetch-commit-detail', ['repoId' => $repository->id, 'sha' => $commit->sha]));
        $response->assertStatus(200);
    }

    public function test_fetch_commit_detail_handles_exceptions()
    {
        // Create a repository
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $commit = Commit::factory()->create([
            'repository_id' => $repository->id,
            'sha' => 'valid_sha'
        ]);

        // Define the route with middleware in the test method
        Route::middleware([CheckIfCommitShaIsValid::class])
            ->get('/{repoId}/commits/{sha}/fetch', [FetchCommitDetailController::class, '__invoke']);

        // Mock the CommitRepositoryInterface
        $commitRepositoryMock = \Mockery::mock(CommitRepositoryInterface::class);
        $commitRepositoryMock->shouldReceive('getCommitFilesBySha')
            ->andThrow(new FailedToFetchCommitWithCommitFiles());

        // Bind the mock to the container
        $this->app->instance(CommitRepositoryInterface::class, $commitRepositoryMock);

        // Create a request
        $commitSha = $commit->sha;
        $request = Request::create("/{$repository->id}/commits/{$commitSha}/fetch", 'GET', [
            'repoId' => $repository->id,
            'sha' => $commitSha,
        ]);

        // Make the request and assert the response
        $response = $this->call($request->method(), $request->path());

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Operation failed!',
            'errors' => []
        ]);
    }
    public function test_get_commit_files_by_sha_handles_exceptions()
    {
        // Drop the commits table to simulate the table not existing
        Schema::drop('commits');

        // Create an instance of the repository
        $commitRepository = new \Modules\Commit\database\repository\CommitRepository();

        $this->expectException(FailedToFetchCommitWithCommitFiles::class);

        // Call the method
        $commitRepository->getCommitFilesBySha(1, '123456');
    }

}

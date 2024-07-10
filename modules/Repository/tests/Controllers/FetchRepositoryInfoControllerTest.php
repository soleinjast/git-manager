<?php

namespace Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Repository\database\repository\RepositoryRepository;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\Exceptions\RepositoryInfoFindFailedException;
use Modules\Repository\src\Middleware\ValidateRepositoryIdForFetchingInfo;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class FetchRepositoryInfoControllerTest extends TestCase
{
    use RefreshDatabase;
    public function test_fetch_repository_info_success()
    {
        $token = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
            'github_token_id' => $token->id,
            'deadline' => now()->addDays(7),
        ]);

        $response = $this->getJson(route('repository.info', ['repoId' => $repository->id]));
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $repository->id,
            'owner' => $repository->owner,
            'name' => $repository->name,
            'github_token_id' => $repository->github_token_id,
            'deadline' => $repository->deadline->toDateTimeString(),

        ]);
    }
    public function test_fetch_repository_info_not_found()
    {
        $response = $this->getJson(route('repository.info', ['repoId' => 999]));

        $response->assertStatus(400);
        $this->assertEquals('Repository not found!', $response->json('message'));
    }

    public function test_fetch_repository_info_found_failed_exception()
    {
        $token = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
            'github_token_id' => $token->id,
            'deadline' => now()->addDays(7),
        ]);
        $repositoryRepositoryMock = Mockery::mock(RepositoryRepositoryInterface::class);
        $repositoryRepositoryMock->shouldReceive('findById')
            ->andThrow(new RepositoryInfoFindFailedException("server error", 500));
        $this->app->instance(RepositoryRepositoryInterface::class, $repositoryRepositoryMock);
        $response = $this->getJson(route('repository.info', ['repoId' => 1]));
        $response->assertStatus(400);
        $this->assertEquals('server error', $response->json('message'));
    }

    public function test_find_by_id_exception_within_middleware()
    {
        // Create an instance of RepositoryRepository
        $repositoryRepository = new RepositoryRepository();

        // Mock the Repository model to throw an exception
        $this->expectException(RepositoryInfoFindFailedException::class);
        Schema::drop('repositories');
        // Call the findById method with a non-existent ID
        $repositoryRepository->findById(1);
    }

    public function test_handle_query_exception()
    {
        $middleware = new ValidateRepositoryIdForFetchingInfo();

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('route')->with('repoId')->andReturn(1);
        $repositoryMock = Mockery::mock(Repository::class);
        Schema::drop('repositories');
        $closure = function () {
            return response()->json(['success' => true]);
        };
        $response = $middleware->handle($requestMock, $closure);

        $this->assertEquals(500, $response->status());
        $this->assertEquals('Operation failed!', $response->getOriginalContent()['message']);
    }
}

<?php

namespace Modules\Repository\tests\Controllers;

use App\Enumerations\GithubApiResponses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Repository\database\repository\RepositoryRepository;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Repository\src\Exceptions\RepositoryCreationFailedException;
use Modules\Repository\src\Exceptions\RepositoryRetrievalFailedException;
use Modules\Repository\src\Exceptions\RepositoryUpdateFailedException;
use Modules\Repository\src\Middleware\CheckUniqueRepository;
use Modules\Repository\src\Middleware\ValidateRepositoryId;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class RepositoryControllerTest extends TestCase
{
    use RefreshDatabase;

    // Test creating a repository successfully
    public function testCanCreateRepositorySuccessfully()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);
        $githubToken = GithubToken::factory()->create();
        $response = $this->post(route('repository.create'), [
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Operation successful'
            ]);
        $this->assertDatabaseCount('repositories', 1);
    }

    // Test creating a repository with missing fields
    public function testCannotCreateRepositoryWithMissingFields()
    {
        $response = $this->post(route('repository.create'));
        $this->assertCount(3, $response->json()['errors']);
        $response->assertStatus(422);
    }

    // Test creating a repository with an invalid deadline
    public function testCannotCreateRepositoryWithInvalidDeadline()
    {
        $githubToken = GithubToken::factory()->create();
        $response = $this->post(route('repository.create'), [
            'owner' => 'test-owner',
            'name' => 'test-name',
            'deadline' => now(),
            'github_token_id' => $githubToken->id,
        ]);
        $response->assertStatus(422);
        $this->assertCount(1, $response->json()['errors']);
    }

    // Test creating a repository with duplicate data
    public function testCannotCreateDuplicateRepository()
    {
        $githubToken = GithubToken::factory()->create();

        Repository::factory()->create([
            'owner' => 'test',
            'name' => 'test',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);
        $response = $this->post(route('repository.create'), [
            'owner' => 'test',
            'name' => 'test',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Repository already exist!'
            ]);
    }

    // Test handling an exception when creating a repository
    public function testErrorHandlingDuringRepositoryCreation()
    {
        Schema::drop('repositories');
        $request = Request::create('/create', 'POST', [
            'owner' => 'test-owner',
            'name' => 'test-name',
            'deadline' => now()->addDays(1),
        ]);
        $middleware = new CheckUniqueRepository();
        $response = $middleware->handle($request, function () {});
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->status());
        $this->assertEquals("Create repository failed!", $response->original['message']);
    }

    // Test repository access failure when creating a repository
    public function testRepositoryAccessFailureDuringCreation()
    {
        $githubToken = GithubToken::factory()->create();
        // Fake the HTTP response from GitHub API
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 404),
        ]);

        // Send the POST request
        $response = $this->postJson(route('repository.create'), [
            'github_token_id' => $githubToken->id,
            'owner' => 'test-owner',
            'name' => 'test-name',
            'deadline' => now()->addDays(1),
        ]);

        // Assert the response
        $response->assertStatus(400);
        $response->assertJson(['message' => RepositoryResponseEnums::REPOSITORY_NOT_FOUND]);
    }

    // Test handling a connection exception when creating a repository
    public function testHandleConnectionExceptionDuringCreation()
    {
        $githubToken = GithubToken::factory()->create();
        // Fake the HTTP response from GitHub API
        Http::fake(function () {
            throw new ConnectionException('Connection error');
        });

        // Send the POST request
        $response = $this->postJson(route('repository.create'), [
            'github_token_id' => $githubToken->id,
            'owner' => 'test-owner',
            'name' => 'test-name',
            'deadline' => now()->addDays(1),
        ]);
        // Assert the response
        $response->assertStatus(500);
        $response->assertJson(['message' => GithubApiResponses::CONNECTION_ERROR]);
    }

    // Test repository creation failure
    public function testCreateRepositoryFailure()
    {
        // Create a CreateRepositoryDetails DTO
        $createRepositoryDetails = new TestCreateRepositoryDetails(null, 'test-owner', 'test-name', now()->addDays(1));
        // Instantiate the repository
        $repositoryRepository = new RepositoryRepository();
        // Expect the RepositoryCreationFailedException to be thrown
        $this->expectException(RepositoryCreationFailedException::class);
        $this->expectExceptionMessage(RepositoryResponseEnums::REPOSITORY_CREATION_FAILED);

        // Call the create method
        $repositoryRepository->create($createRepositoryDetails);
    }

    // Test catching an exception during repository creation
    public function testCatchExceptionDuringRepositoryCreation()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);
        $githubToken = GithubToken::factory()->create();
        // Mock the RepositoryRepositoryInterface
        $repositoryRepositoryMock = Mockery::mock(RepositoryRepositoryInterface::class);
        $repositoryRepositoryMock->shouldReceive('create')
            ->andThrow(new RepositoryCreationFailedException(RepositoryResponseEnums::REPOSITORY_CREATION_FAILED, 500));

        // Bind the mock to the service container
        $this->app->instance(RepositoryRepositoryInterface::class, $repositoryRepositoryMock);

        // Send the POST request
        $response = $this->postJson(route('repository.create'), [
            'github_token_id' => $githubToken->id,
            'owner' => 'test-owner',
            'name' => 'test-name',
            'deadline' => now()->addDays(1),
        ]);
        // Assert the response
        $response->assertStatus(500);
        $response->assertJson([
            'message' => RepositoryResponseEnums::REPOSITORY_CREATION_FAILED,
        ]);
    }

    // Test updating a repository successfully
    public function testCanUpdateRepositorySuccessfully()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);
        $githubToken = GithubToken::factory()->create();
        $response = $this->post(route('repository.create'), [
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Operation successful'
            ]);
        $this->assertDatabaseCount('repositories', 1);

        $repository = Repository::query()->first();
        $response = $this->post(route('repository.update', $repository->id), [
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(2),
        ]);
        $response->assertStatus(200);
    }

    // Test updating a repository with invalid deadline
    public function testCannotUpdateRepositoryWithInvalidDeadline()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);
        $githubToken = GithubToken::factory()->create();
        $response = $this->post(route('repository.create'), [
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Operation successful'
            ]);
        $this->assertDatabaseCount('repositories', 1);

        $repository = Repository::query()->first();
        $response = $this->post(route('repository.update', $repository->id), [
            'github_token_id' => $githubToken->id,
            'deadline' => now()->subDays(2),
        ]);
        $response->assertStatus(422);
    }

    // Test updating a repository with invalid repository ID
    public function testCannotUpdateRepositoryWithInvalidId()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);


        $githubToken = GithubToken::factory()->create();
        $response = $this->post(route('repository.create'), [
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Operation successful'
            ]);
        $this->assertDatabaseCount('repositories', 1);

        $repository = Repository::query()->first();
        $response = $this->post(route('repository.update', $repository->id + 1), [
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDay(),
        ]);
        $response->assertStatus(400);
    }

    // Test catching an exception during repository update
    public function testCatchExceptionDuringRepositoryUpdate()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);


        $githubToken = GithubToken::factory()->create();
        $response = $this->post(route('repository.create'), [
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Operation successful'
            ]);
        $this->assertDatabaseCount('repositories', 1);

        // Mock the RepositoryRepositoryInterface
        $repositoryRepositoryMock = Mockery::mock(RepositoryRepositoryInterface::class);
        $repositoryRepositoryMock->shouldReceive('update')
            ->andThrow(new RepositoryUpdateFailedException(RepositoryResponseEnums::REPOSITORY_CREATION_FAILED, 500));

        // Bind the mock to the service container
        $this->app->instance(RepositoryRepositoryInterface::class, $repositoryRepositoryMock);

        $repository = Repository::query()->first();
        $response = $this->post(route('repository.update', $repository->id), [
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDay(),
        ]);
        $response->assertStatus(500);
    }

    // Test repository update failure
    public function testUpdateRepositoryFailure()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);


        $githubToken = GithubToken::factory()->create();
        $response = $this->post(route('repository.create'), [
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Operation successful'
            ]);
        $this->assertDatabaseCount('repositories', 1);

        $repository = Repository::query()->first();

        // Create a CreateRepositoryDetails DTO
        $createRepositoryDetails = new TestUpdateRepositoryDetails($repository->id, null, 'test-owner', 'test-name', now()->addDays(1));
        // Instantiate the repository
        $repositoryRepository = new RepositoryRepository();
        // Expect the RepositoryUpdateFailedException to be thrown
        $this->expectException(RepositoryUpdateFailedException::class);
        $this->expectExceptionMessage(RepositoryResponseEnums::REPOSITORY_UPDATE_FAILED);

        // Call the update method
        $repositoryRepository->update($createRepositoryDetails);
    }

    // Test handling a database connection error
    public function testHandleDatabaseConnectionError()
    {
        // Simulate a database connection error
        Schema::drop('repositories');

        // Simulate a request object
        $request = Request::create('/test/1', 'POST');

        // Call the middleware handle method directly
        $middleware = new ValidateRepositoryId();

        // Capture the response from the middleware
        $response = $middleware->handle($request, function () {});
        // Assert that the response status is 500
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->status());
    }

    public function testCanRetrieveAllRepositories()
    {
        $githubTokens = GithubToken::factory()->count(2)->create();
        Repository::factory()->create([
            'owner' => 'test',
            'name' => 'test',
            'github_token_id' => $githubTokens[0]->id,
            'deadline' => now()->addDay(),
        ]);
        Repository::factory()->create([
            'owner' => 'test',
            'name' => 'test',
            'github_token_id' => $githubTokens[1]->id,
            'deadline' => now()->addDay(),
        ]);
        $response = $this->get(route('repository.fetch'));
        $this->assertCount(2, $response->json()['data']);
        $response->assertStatus(200);
    }

    // Test successful retrieval of repositories with search by name
    public function testCanRetrieveRepositoriesWithSearchByName()
    {
        $githubTokens = GithubToken::factory()->count(2)->create();
        Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo-1',
            'github_token_id' => $githubTokens[0]->id,
            'deadline' => now()->addDay(),
        ]);
        Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'another-repo',
            'github_token_id' => $githubTokens[1]->id,
            'deadline' => now()->addDay(),
        ]);
        $response = $this->get(route('repository.fetch', ['search_name' => 'test-repo']));
        $this->assertCount(1, $response->json()['data']);
        $response->assertStatus(200);
    }
    public function testCanRetrieveRepositoriesWithSearchByOwner()
    {
        $githubTokens = GithubToken::factory()->count(2)->create();
        Repository::factory()->create([
            'owner' => 'owner1',
            'name' => 'repo-1',
            'github_token_id' => $githubTokens[0]->id,
            'deadline' => now()->addDay(),
        ]);
        Repository::factory()->create([
            'owner' => 'owner2',
            'name' => 'repo-2',
            'github_token_id' => $githubTokens[1]->id,
            'deadline' => now()->addDay(),
        ]);
        $response = $this->get(route('repository.fetch', ['search_owner' => 'owner1']));
        $this->assertCount(1, $response->json()['data']);
        $response->assertStatus(200);
    }

    public function testCanRetrieveRepositoriesWithSearchByNameAndOwner()
    {
        $githubTokens = GithubToken::factory()->count(2)->create();
        Repository::factory()->create([
            'owner' => 'owner1',
            'name' => 'repo-1',
            'github_token_id' => $githubTokens[0]->id,
            'deadline' => now()->addDay(),
        ]);
        Repository::factory()->create([
            'owner' => 'owner2',
            'name' => 'repo-2',
            'github_token_id' => $githubTokens[1]->id,
            'deadline' => now()->addDay(),
        ]);
        $response = $this->get(route('repository.fetch', ['search_name' => 'repo-1', 'search_owner' => 'owner1']));
        $this->assertCount(1, $response->json()['data']);
        $response->assertStatus(200);
    }
    public function testErrorHandlingDuringRepositoryRetrieval()
    {
        // Mock the RepositoryRepository to throw an exception
        $repositoryRepositoryMock = Mockery::mock(RepositoryRepositoryInterface::class);
        $repositoryRepositoryMock->shouldReceive('fetchAll')
            ->andThrow(new RepositoryRetrievalFailedException(RepositoryResponseEnums::REPOSITORY_RETRIEVAL_FAILED, 500));

        // Bind the mock to the service container
        $this->app->instance(RepositoryRepositoryInterface::class, $repositoryRepositoryMock);

        // Call the route
        $response = $this->get(route('repository.fetch'));


        // Assert the response
        $response->assertStatus(500);
        $response->assertJson(['message' => RepositoryResponseEnums::REPOSITORY_RETRIEVAL_FAILED]);
    }

    public function testDatabaseQueryFailureOnFetchMethod()
    {
        Schema::drop('repositories');
        // Create the RepositoryRepository instance
        $repository = app(RepositoryRepositoryInterface::class);
        // Expect an exception to be thrown
        $this->expectException(RepositoryRetrievalFailedException::class);
        // When we fetch the repositories
        $repository->fetchAll();
    }
}

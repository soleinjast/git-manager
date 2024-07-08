<?php

namespace Modules\Repository\tests\Controllers;

use App\Enumerations\GithubApiResponses;
use App\Services\GithubService;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Repository\database\repository\BranchRepository;
use Modules\Repository\database\repository\BranchRepositoryInterface;
use Modules\Repository\database\repository\RepositoryRepository;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\DTOs\BranchDto;
use Modules\Repository\src\DTOs\CreateBranchDetails;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Repository\src\Events\BranchCreated;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Exceptions\RepositoryCreationFailedException;
use Modules\Repository\src\Exceptions\RepositoryRetrievalFailedException;
use Modules\Repository\src\Exceptions\RepositoryUpdateFailedException;
use Modules\Repository\src\Listeners\StoreBranches;
use Modules\Repository\src\Middleware\CheckUniqueRepository;
use Modules\Repository\src\Middleware\ValidateRepositoryId;
use Modules\Repository\src\Models\Branch;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class RepositoryControllerTest extends TestCase
{
    use RefreshDatabase;
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
        $this->assertCount(4, $response->json()['errors']);
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

    public function testRepositoryCreatedEventIsDispatched()
    {
        Event::fake();
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);
        $githubToken = GithubToken::factory()->create();

        $response = $this->postJson(route('repository.create'), [
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(RepositoryCreated::class, function ($event) {
            return $event->repository->owner === 'test-owner' &&
                $event->repository->name === 'test-name' &&
                $event->repository->deadline === now()->addDay(1)->toDateTimeString();
        });
    }
    public function testStoreBranchesListener()
    {
        $githubToken = GithubToken::factory()->create();

        Http::fake([
            'https://api.github.com/repos/test-owner/test-name/branches' => Http::response([
                ['name' => 'main'],
                ['name' => 'dev'],
            ], 200),
        ]);

        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $event = new RepositoryCreated($repositoryDto);

        $branchRepositoryMock = Mockery::mock(BranchRepositoryInterface::class);
        $branchRepositoryMock->shouldReceive('create')
            ->twice()
            ->andReturnUsing(function (CreateBranchDetails $details) {
                return new BranchDto(
                    Branch::query()->create($details->toArray())->id,
                    $details->repositoryId,
                    $details->branchName
                );
            });

        $listener = new StoreBranches(app('events'), $branchRepositoryMock);
        $listener->handle($event);

        $this->assertDatabaseCount('branches', 2);
        $this->assertDatabaseHas('branches', [
            'repository_id' => $repository->id,
            'name' => 'main',
        ]);
        $this->assertDatabaseHas('branches', [
            'repository_id' => $repository->id,
            'name' => 'dev',
        ]);
    }
    public function testHandleConnectionException()
    {
        Event::fake();
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        Http::fake(function () {
            throw new ConnectionException('Connection error');
        });

        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $event = new RepositoryCreated($repositoryDto);

        $branchRepositoryMock = Mockery::mock(BranchRepositoryInterface::class);
        $branchRepositoryMock->shouldReceive('create')->never();

        $listener = new StoreBranches(app('events'), $branchRepositoryMock);
        $listener->handle($event);

        $this->assertDatabaseCount('branches', 0); // No branches should be created
    }

    public function test_handle_general_exception()
    {
        Event::fake();
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name' => Http::response(null, 200),
        ]);
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        Http::fake(function () {
            throw new Exception('General error');
        });

        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $event = new RepositoryCreated($repositoryDto);

        $branchRepositoryMock = Mockery::mock(BranchRepositoryInterface::class);
        $branchRepositoryMock->shouldReceive('create')->never();

        $listener = new StoreBranches(app('events'), $branchRepositoryMock);
        $listener->handle($event);

        $this->assertDatabaseCount('branches', 0); // No branches should be created
    }
    public function testCreateBranchThrowsException()
    {
        $this->expectException(Exception::class);

        $branchRepository = new BranchRepository();

        // Simulate a database connection error by dropping the branches table
        Schema::drop('branches');

        $createBranchDetails = new CreateBranchDetails(
            repositoryId: 1,
            branchName: 'main'
        );

        $branchRepository->create($createBranchDetails);
    }

    public function testBranchDtoFromEloquent()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create(); // Ensure repository exists

        $branch = Branch::factory()->create([
            'repository_id' => $repository->id,
            'name' => 'main'
        ]);

        $branchDto = BranchDto::fromEloquent($branch);

        $this->assertInstanceOf(BranchDto::class, $branchDto);
        $this->assertEquals($branch->id, $branchDto->id);
        $this->assertEquals($branch->repository_id, $branchDto->repositoryId);
        $this->assertEquals($branch->name, $branchDto->name);
    }

    /**
     * @throws Exception
     */
    public function testCreateBranchSuccessfully()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create(); // Ensure repository exists

        $branchRepository = new BranchRepository();

        $createBranchDetails = new CreateBranchDetails(
            repositoryId: $repository->id,
            branchName: 'main'
        );

        $branchDto = $branchRepository->create($createBranchDetails);

        $this->assertInstanceOf(BranchDto::class, $branchDto);
        $this->assertDatabaseHas('branches', [
            'repository_id' => $branchDto->repositoryId,
            'name' => $branchDto->name,
        ]);
    }
    public function test_fetch_branches_throws_general_exception()
    {
        $githubService = new GithubService();
        $githubService->setModel('fake-token', 'fake-owner', 'fake-repo');

        Http::fake([
            'https://api.github.com/repos/fake-owner/fake-repo/branches' => Http::response([], 500)
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(GithubApiResponses::SERVER_ERROR);

        $githubService->fetchBranches();
    }

    public function test_dispatches_branch_created_event()
    {
        // Create a fake GitHub token and repository
        $githubToken = GithubToken::factory()->create();

        // Fake the HTTP response for fetching branches from GitHub
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name/branches' => Http::response([
                ['name' => 'main'],
            ], 200),
        ]);

        // Create a repository
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        // Create a RepositoryDto from the repository
        $repositoryDto = RepositoryDto::fromEloquent($repository);

        // Create the RepositoryCreated event
        $event = new RepositoryCreated($repositoryDto);

        // Mock the BranchRepositoryInterface
        $branchRepositoryMock = Mockery::mock(BranchRepositoryInterface::class);
        $branchRepositoryMock->shouldReceive('create')
            ->andReturnUsing(function ($createBranchDetails) {
                return new BranchDto(1, $createBranchDetails->repositoryId, $createBranchDetails->branchName);
            });

        // Mock the event dispatcher
        $dispatcherMock = Mockery::mock(Dispatcher::class);
        $dispatcherMock->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(BranchCreated::class));

        // Bind the mock to the service container
        $this->app->instance(BranchRepositoryInterface::class, $branchRepositoryMock);
        $this->app->instance(Dispatcher::class, $dispatcherMock);

        // Create the StoreBranches listener
        $listener = new StoreBranches($dispatcherMock, $branchRepositoryMock);

        // Handle the event
        $listener->handle($event);
    }
}

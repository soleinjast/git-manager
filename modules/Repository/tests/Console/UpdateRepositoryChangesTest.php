<?php

namespace Console;

use App\Services\GithubService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Commit\database\repository\CommitRepositoryInterface;
use Modules\Commit\src\Exceptions\ChunkAllRepositoriesFailedException;
use Modules\Commit\src\Jobs\ProcessCommit;
use Modules\Commit\src\Listeners\StoreCommits;
use Modules\Commit\src\Listeners\UpdateCommits;
use Modules\Repository\database\repository\BranchRepository;
use Modules\Repository\database\repository\BranchRepositoryInterface;
use Modules\Repository\database\repository\RepositoryRepository;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\Console\UpdateRepositoryChanges;
use Modules\Repository\src\DTOs\BranchDto;
use Modules\Repository\src\DTOs\CreateBranchDetails;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Events\BranchCreated;
use Modules\Repository\src\Events\BranchUpdated;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Events\RepositoryUpdate;
use Modules\Repository\src\Listeners\UpdateBranches;
use Modules\Repository\src\Models\Branch;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Modules\User\src\Jobs\CreateUser;
use Modules\User\src\Listeners\StoreCollaborators;
use Modules\User\src\Listeners\UpdateCollaborators;
use Tests\TestCase;

class UpdateRepositoryChangesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_events_for_each_repository()
    {
        Event::fake();
        $this->artisan('update-repository-changes')
            ->assertExitCode(0);

        $githubToken = GithubToken::factory()->create();
        $repository1 = Repository::factory()->create();
        $repository2 = Repository::factory()->create();

        $repositoryRepositoryMock = \Mockery::mock(RepositoryRepositoryInterface::class);
        $repositoryRepositoryMock->shouldReceive('chunkAll')
            ->once()
            ->with(10, \Mockery::on(function ($callback) use ($repository1, $repository2) {
                $callback(collect([$repository1, $repository2]));
                return true;
            }));

        $this->app->instance(RepositoryRepositoryInterface::class, $repositoryRepositoryMock);

        $command = new UpdateRepositoryChanges(app('events'), $repositoryRepositoryMock);
        $command->handle();

        Event::assertDispatched(RepositoryUpdate::class, function ($event) use ($repository1) {
            return $event->repository->id === $repository1->id;
        });

        Event::assertDispatched(RepositoryUpdate::class, function ($event) use ($repository2) {
            return $event->repository->id === $repository2->id;
        });

        Event::assertDispatchedTimes(RepositoryUpdate::class, 2);
    }

    public function test_command_handles_exceptions()
    {
        $repositoryRepositoryMock = \Mockery::mock(RepositoryRepositoryInterface::class);
        $repositoryRepositoryMock->shouldReceive('chunkAll')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->app->instance(RepositoryRepositoryInterface::class, $repositoryRepositoryMock);

        Log::shouldReceive('error')->once();

        $this->expectException(ChunkAllRepositoriesFailedException::class);

        $command = new UpdateRepositoryChanges(app('events'), $repositoryRepositoryMock);
        $command->handle();
    }


    /**
     * @throws ChunkAllRepositoriesFailedException
     */
    public function test_chunk_all_calls_callback_with_repositories()
    {
        $githubToken = GithubToken::factory()->create();
        $repository1 = Repository::factory()->create();
        $repository2 = Repository::factory()->create();

        $repositoryRepository = new RepositoryRepository();

        $chunkSize = 1;
        $callbackCalled = false;

        $repositoryRepository->chunkAll($chunkSize, function ($repositories) use (&$callbackCalled, $repository1, $repository2) {
            $callbackCalled = true;
            $this->assertTrue($repositories->contains($repository1) || $repositories->contains($repository2));
        });

        $this->assertTrue($callbackCalled);
    }

    public function test_chunk_all_handles_exceptions()
    {
        $repositoryRepository = new RepositoryRepository();

        // Simulate a database exception by dropping the repositories table
        Schema::drop('repositories');

        $this->expectException(ChunkAllRepositoriesFailedException::class);

        Log::shouldReceive('error')->once();

        $repositoryRepository->chunkAll(1, function ($repositories) {
            // This callback should not be called due to the exception
        });
    }

    public function test_handle_updates_branches_and_dispatches_events()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
            'github_token_id' => 1
        ]);
        $repositoryDto = RepositoryDto::fromEloquent($repository);

        $branchesData = [
            ['name' => 'main'],
            ['name' => 'develop']
        ];

        Http::fake([
            'https://api.github.com/repos/test-owner/test-repo/branches' => Http::response($branchesData, 200),
        ]);

        $branchRepositoryMock = Mockery::mock(BranchRepositoryInterface::class);
        $branchRepositoryMock->shouldReceive('updateOrCreate')
            ->twice()
            ->andReturnUsing(function (CreateBranchDetails $details) {
                $branch = Branch::factory()->create([
                    'repository_id' => $details->repositoryId,
                    'name' => $details->branchName,
                ]);
                return BranchDto::fromEloquent($branch);
            });

        Event::fake();

        $listener = new UpdateBranches(Event::getFacadeRoot(), $branchRepositoryMock);
        $listener->handle(new RepositoryUpdate($repositoryDto));

        Event::assertDispatched(BranchUpdated::class, function ($event) use ($repository) {
            return $event->repository->id === $repository->id;
        });
    }

    public function test_handle_logs_connection_exception()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $repositoryDto = RepositoryDto::fromEloquent($repository);

        Http::fake([
            'https://api.github.com/repos/' . $repository->owner . '/' . $repository->name . '/branches' => Http::response(null, 500),
        ]);

        $branchRepositoryMock = Mockery::mock(BranchRepositoryInterface::class);
        $branchRepositoryMock->shouldNotReceive('updateOrCreate');

        $listener = new UpdateBranches(Event::getFacadeRoot(), $branchRepositoryMock);

        Log::shouldReceive('error')->once();

        $listener->handle(new RepositoryUpdate($repositoryDto));
    }

    public function test_handle_logs_generic_exception()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $repositoryDto = RepositoryDto::fromEloquent($repository);

        Http::fake([
            'https://api.github.com/repos/' . $repository->owner . '/' . $repository->name . '/branches' => Http::response([
                ['name' => 'main']
            ], 200),
        ]);

        $branchRepositoryMock = Mockery::mock(BranchRepositoryInterface::class);
        $branchRepositoryMock->shouldReceive('updateOrCreate')
            ->andThrow(new \Exception('Generic error'));

        $listener = new UpdateBranches(Event::getFacadeRoot(), $branchRepositoryMock);

        Log::shouldReceive('error')->once();

        $listener->handle(new RepositoryUpdate($repositoryDto));
    }

    /**
     * @throws \Exception
     */
    public function test_update_or_create_creates_new_branch()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $branchRepository = new BranchRepository();

        $createBranchDetails = new CreateBranchDetails(
            repositoryId: 1,
            branchName: 'main'
        );

        $branchDto = $branchRepository->updateOrCreate($createBranchDetails);

        $this->assertInstanceOf(BranchDto::class, $branchDto);
        $this->assertDatabaseHas('branches', [
            'repository_id' => 1,
            'name' => 'main',
        ]);
    }

    public function test_update_or_create_updates_existing_branch()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $branch = Branch::factory()->create([
            'repository_id' => 1,
            'name' => 'main'
        ]);

        $branchRepository = new BranchRepository();

        $createBranchDetails = new CreateBranchDetails(
            repositoryId: 1,
            branchName: 'main'
        );

        $branchDto = $branchRepository->updateOrCreate($createBranchDetails);

        $this->assertInstanceOf(BranchDto::class, $branchDto);
        $this->assertEquals($branch->id, $branchDto->id);
        $this->assertDatabaseHas('branches', [
            'repository_id' => 1,
            'name' => 'main',
        ]);
    }

    public function test_update_or_create_handles_exceptions()
    {
        $branchRepository = new BranchRepository();

        // Drop the branches table to simulate a database exception
        Schema::drop('branches');

        $createBranchDetails = new CreateBranchDetails(
            repositoryId: 1,
            branchName: 'main'
        );

        $this->expectException(\Exception::class);

        $branchRepository->updateOrCreate($createBranchDetails);
    }


    public function test_handle_reports_connection_exception()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        // Create a repository and its DTO
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
            'github_token_id' => 1
        ]);
        $repositoryDto = RepositoryDto::fromEloquent($repository);

        // Mock the BranchRepositoryInterface
        $branchRepositoryMock = Mockery::mock(BranchRepositoryInterface::class);

        // Fake the event facade
        Event::fake();

        // Expect the report function to be called
        Log::shouldReceive('error')->once();

        // Mock the github_service function to throw a ConnectionException
        $mockGithubService = Mockery::mock(GithubService::class);
        Http::fake(function () {
            throw new ConnectionException('Connection error');
        });

        // Override the github_service helper function to return the mocked service
        $this->app->instance(GithubService::class, $mockGithubService);

        // Create an instance of the listener
        $listener = new UpdateBranches(Event::getFacadeRoot(), $branchRepositoryMock);

        // Handle the event, which should trigger the exception handling
        $listener->handle(new RepositoryUpdate($repositoryDto));

        // Assert that the report function was called
        $this->assertTrue(true);  // Assert that the test passes
    }
    public function test_handle_dispatches_process_commit_jobs()
    {
        Queue::fake();

        // Create a repository and branch
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $branchDto = new BranchDto(1, $repository->id, 'main');

        // Fake the GitHub API response
        Http::fake([
            'https://api.github.com/repos/test-owner/test-repo/commits?sha=main&page=1&per_page=100' => Http::response([
                [
                    'sha' => '123456',
                    'commit' => [
                        'message' => 'Initial commit',
                        'author' => [
                            'name' => 'John Doe',
                            'date' => '2024-06-22T12:00:00Z',
                        ],
                    ],
                    'author' => [
                        'id' => 1,
                    ],
                    'parents' => [],
                ],
                [
                    'sha' => '789012',
                    'commit' => [
                        'message' => 'Add feature',
                        'author' => [
                            'name' => 'Jane Doe',
                            'date' => '2024-06-22T13:00:00Z',
                        ],
                    ],
                    'author' => [
                        'id' => 2,
                    ],
                    'parents' => ['123456'],
                ],
            ], 200),
            'https://api.github.com/repos/test-owner/test-repo/commits?sha=main&page=2&per_page=100' => Http::response([], 200),
        ]);

        $listener = new UpdateCommits();
        $listener->handle(new BranchUpdated($branchDto, $repositoryDto));

        // Assert that the ProcessCommit jobs were dispatched
        Queue::assertPushed(ProcessCommit::class, function ($job) {
            return $job->commitData['sha'] === '123456';
        });

        Queue::assertPushed(ProcessCommit::class, function ($job) {
            return $job->commitData['sha'] === '789012';
        });

        Queue::assertPushed(ProcessCommit::class, 2);
    }

    public function test_handle_handles_connection_exception()
    {
        Queue::fake();

        // Create a repository and branch
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);
        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $branchDto = new BranchDto(1, $repository->id, 'main');

        $listener = new UpdateCommits();
        $listener->handle(new BranchUpdated($branchDto, $repositoryDto));

        // Fake the GitHub API response
        Http::fake(function () {
            throw new ConnectionException('Connection error');
        });

        $listener = new UpdateCommits();
        $listener->handle(new BranchUpdated($branchDto, $repositoryDto));

        // Assert that no jobs were dispatched
        Queue::assertNothingPushed();
    }

    public function test_handle_handles_general_exception()
    {
        Queue::fake();

        // Create a repository and branch
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-repo',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $branchDto = new BranchDto(1, $repository->id, 'main');

        // Fake the GitHub API response
        Http::fake(function () {
            throw new \Exception('General error');
        });

        // Create the event and listener
        $listener = new UpdateCommits();
        $listener->handle(new BranchUpdated($branchDto, $repositoryDto));
        // Assert that no jobs were dispatched
        Queue::assertNothingPushed();
    }

    public function test_it_handles_repository_created_event_and_dispatches_jobs()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name/collaborators' => Http::response([
                [
                    'login' => 'collaborator1',
                    'id' => 1,
                    'avatar_url' => 'https://example.com/avatar1.png',
                ],
                [
                    'login' => 'collaborator2',
                    'id' => 2,
                    'avatar_url' => 'https://example.com/avatar2.png',
                ]
            ], 200),
            'https://api.github.com/users/collaborator1' => Http::response([
                'login' => 'collaborator1',
                'name' => 'Collaborator One'
            ], 200),
            'https://api.github.com/users/collaborator2' => Http::response([
                'login' => 'collaborator2',
                'name' => 'Collaborator Two'
            ], 200),
            '*' => Http::response(['message' => 'Unexpected URL called'], 500)
        ]);

        $githubToken = GithubToken::factory()->create();

        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $repositoryDto = RepositoryDto::fromEloquent($repository);

        Queue::fake();
        $event = new RepositoryUpdate($repositoryDto);
        $listener = new UpdateCollaborators();
        $listener->handle($event);

        Queue::assertPushed(CreateUser::class, function ($job) use ($repositoryDto) {
            return $job->repository->id === $repositoryDto->id;
        });

        Queue::assertPushed(CreateUser::class, 2);
    }
}

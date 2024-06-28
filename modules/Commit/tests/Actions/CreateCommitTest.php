<?php

namespace Modules\Commit\tests\Actions;


use App\Enumerations\GithubApiResponses;
use App\Services\GithubService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Commit\database\repository\CommitRepository;
use Modules\Commit\database\repository\CommitRepositoryInterface;
use Modules\Commit\src\DTOs\CommitDto;
use Modules\Commit\src\DTOs\CreateCommitDetails;
use Modules\Commit\src\Events\CommitCreated;
use Modules\Commit\src\Exceptions\CommitUpdateOrCreateFailedException;
use Modules\Commit\src\Exceptions\FailedToCheckIfCommitExistsException;
use Modules\Commit\src\Jobs\ProcessCommit;
use Modules\Commit\src\Listeners\StoreCommits;
use Modules\Commit\src\Models\Commit;
use Modules\Repository\src\DTOs\BranchDto;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Events\BranchCreated;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;

class CreateCommitTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_dispatches_process_commit_jobs()
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

        // Create the event and listener
        $event = new BranchCreated($branchDto, $repositoryDto);
        $listener = new StoreCommits(app(CommitRepositoryInterface::class));

        // Handle the event
        $listener->handle($event);

        // Assert that the ProcessCommit jobs were dispatched
        Queue::assertPushed(ProcessCommit::class, function ($job) {
            return $job->commitData['sha'] === '123456';
        });

        Queue::assertPushed(ProcessCommit::class, function ($job) {
            return $job->commitData['sha'] === '789012';
        });

        Queue::assertPushed(ProcessCommit::class, 2);
    }

    public function test_fetch_commits_throws_server_error_exception()
    {
        // Arrange
        $token = 'fake-token';
        $owner = 'test-owner';
        $name = 'test-repo';
        $branch = 'main';

        $githubService = new GithubService();
        $githubService->setModel($token, $owner, $name);

        // Fake the HTTP response to simulate a server error
        Http::fake([
            "https://api.github.com/repos/{$owner}/{$name}/commits" => Http::response([], 500),
        ]);

        // Assert that the Exception is thrown with the correct message
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(GithubApiResponses::SERVER_ERROR);

        // Act
        $githubService->fetchCommits($branch);
    }
    public function test_it_handles_connection_exception()
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
            throw new ConnectionException('Connection error');
        });

        // Create the event and listener
        $event = new BranchCreated($branchDto, $repositoryDto);
        $listener = new StoreCommits(app(CommitRepositoryInterface::class));

        // Handle the event
        $listener->handle($event);

        // Assert that no jobs were dispatched
        Queue::assertNothingPushed();
    }



    public function test_it_handles_general_exception()
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
        $event = new BranchCreated($branchDto, $repositoryDto);
        $listener = new StoreCommits(app(CommitRepositoryInterface::class));

        // Handle the event
        $listener->handle($event);

        // Assert that no jobs were dispatched
        Queue::assertNothingPushed();
    }

    public function test_handle_method_fires_commit_created_event_with_correct_data()
    {
        $this->withoutExceptionHandling();
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();

        // Create a CreateCommitDetails DTO with test data
        $createCommitDetails = new CreateCommitDetails(
            repositoryId: $repository->id,
            sha: '123456',
            message: 'Initial commit',
            author: 'John Doe',
            date: '2024-06-22T12:00:00Z',
            is_first_commit: true,
            author_git_id: 1
        );
        $commit = Commit::factory()->create([
            'repository_id' => $repository->id,
            'sha' => '123456',
            'message' => 'Initial commit',
            'author' => 'John Doe',
            'date' => '2024-06-22T12:00:00Z',
            'is_first_commit' => true,
            'author_git_id' => 1,
        ]);
        $commitDto = CommitDto::fromEloquent($commit);
        // Mock the CommitRepositoryInterface
        $commitRepositoryMock = Mockery::mock(CommitRepositoryInterface::class);
        $commitRepositoryMock->shouldReceive('existsByShaAndRepositoryId')
            ->andReturn(false);
        $commitRepositoryMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (CreateCommitDetails $details) use ($createCommitDetails) {
                return $details->repositoryId === $createCommitDetails->repositoryId &&
                    $details->sha === $createCommitDetails->sha &&
                    $details->message === $createCommitDetails->message &&
                    $details->author === $createCommitDetails->author &&
                    $details->date === $createCommitDetails->date &&
                    $details->is_first_commit === $createCommitDetails->is_first_commit &&
                    $details->author_git_id === $createCommitDetails->author_git_id;
            }))
            ->andReturn($commitDto);

        // Fake the event helper
        Event::fake();

        // Create an instance of the job
        $job = new ProcessCommit(RepositoryDto::fromEloquent($repository), [
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
        ]);

        // Call the handle method
        $job->handle($commitRepositoryMock);

        // Assert that the CommitCreated event was dispatched with the correct data
        Event::assertDispatched(CommitCreated::class, function ($event) use ($commitDto, $repository) {
            return $event->commit->id === $commitDto->id &&
                $event->commit->sha === $commitDto->sha &&
                $event->commit->message === $commitDto->message &&
                $event->commit->author === $commitDto->author &&
                $event->commit->date === $commitDto->date &&
                $event->repositoryDto->id === $repository->id &&
                $event->repositoryDto->name === $repository->name;
        });
    }

    public function test_update_or_create_creates_new_commit()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        // Create a CreateCommitDetails DTO with test data
        $createCommitDetails = new CreateCommitDetails(
            repositoryId: 1,
            sha: '123456',
            message: 'Initial commit',
            author: 'John Doe',
            date: '2024-06-22T12:00:00Z',
            is_first_commit: true,
            author_git_id: 1
        );

        // Create an instance of the repository
        $repository = new CommitRepository();

        // Call the updateOrCreate method
        $commitDto = $repository->create($createCommitDetails);

        // Assertions
        $this->assertDatabaseHas('commits', [
            'repository_id' => $createCommitDetails->repositoryId,
            'sha' => $createCommitDetails->sha,
            'message' => $createCommitDetails->message,
            'author' => $createCommitDetails->author,
            'date' => $createCommitDetails->date,
            'is_first_commit' => $createCommitDetails->is_first_commit,
            'author_git_id' => $createCommitDetails->author_git_id,
        ]);

        $this->assertEquals($createCommitDetails->sha, $commitDto->sha);
        $this->assertEquals($createCommitDetails->message, $commitDto->message);
    }

    public function test_update_or_create_updates_existing_commit()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        // Create a commit record
        $existingCommit = Commit::factory()->create([
            'repository_id' => 1,
            'sha' => '123456',
            'message' => 'Initial commit',
            'author' => 'John Doe',
            'date' => '2024-06-22T12:00:00Z',
            'is_first_commit' => true,
            'author_git_id' => 1,
        ]);

        // Create a CreateCommitDetails DTO with updated data
        $createCommitDetails = new CreateCommitDetails(
            repositoryId: 1,
            sha: '123456',
            message: 'Initial commit',
            author: 'John Doe',
            date: '2024-06-22T12:00:00Z',
            is_first_commit: true,
            author_git_id: 1
        );

        // Create an instance of the repository
        $repository = new CommitRepository();

        // Call the updateOrCreate method
        $commitDto = $repository->create($createCommitDetails);

        // Assertions
        $this->assertDatabaseHas('commits', [
            'id' => $existingCommit->id,
            'repository_id' => $createCommitDetails->repositoryId,
            'sha' => $createCommitDetails->sha,
            'message' => $createCommitDetails->message,
            'author' => $createCommitDetails->author,
            'date' => $createCommitDetails->date,
            'is_first_commit' => 1,
            'author_git_id' => $createCommitDetails->author_git_id,
        ]);

        $this->assertEquals($createCommitDetails->sha, $commitDto->sha);
        $this->assertEquals($createCommitDetails->message, $commitDto->message);
    }

    public function test_update_or_create_throws_exception_on_failure()
    {
        // Create a CreateCommitDetails DTO with test data
        $createCommitDetails = new CreateCommitDetails(
            repositoryId: 1,
            sha: '123456',
            message: 'Initial commit',
            author: 'John Doe',
            date: '2024-06-22T12:00:00Z',
            is_first_commit: true,
            author_git_id: 1
        );

        // Mock the Commit model to throw an exception
        Schema::drop('commits');

        // Create an instance of the repository
        $repository = new CommitRepository();

        // Expect a CommitUpdateOrCreateFailedException to be thrown
        $this->expectException(CommitUpdateOrCreateFailedException::class);

        // Call the updateOrCreate method
        $repository->create($createCommitDetails);
    }

    public function test_handle_method_does_not_dispatch_event_if_commit_exists()
    {
        Event::fake();
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $repositoryDto = RepositoryDto::fromEloquent($repository);

        $commitData = [
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
        ];

        // Create an existing commit
        Commit::factory()->create([
            'repository_id' => $repository->id,
            'sha' => '123456',
            'message' => 'Initial commit',
            'author' => 'John Doe',
            'date' => '2024-06-22 12:00:00',
        ]);

        // Mock the CommitRepositoryInterface
        $commitRepositoryMock = \Mockery::mock(CommitRepositoryInterface::class);
        $commitRepositoryMock->shouldReceive('existsByShaAndRepositoryId')
            ->with('123456', $repository->id)
            ->andReturn(true);
        $commitRepositoryMock->shouldNotReceive('create');

        // Create an instance of the job
        $job = new ProcessCommit($repositoryDto, $commitData);

        // Call the handle method
        $job->handle($commitRepositoryMock);

        // Assert that the CommitCreated event was not dispatched
        Event::assertNotDispatched(CommitCreated::class);
    }

    public function test_handle_method_handles_exceptions_in_commit_exists()
    {
        Event::fake();

        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $repositoryDto = RepositoryDto::fromEloquent($repository);

        $commitData = [
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
        ];

        // Drop the commits table to simulate the table not existing
        Schema::drop('commits');

        // Mock the CommitRepositoryInterface
        $commitRepositoryMock = \Mockery::mock(CommitRepositoryInterface::class);
        $commitRepositoryMock->shouldReceive('existsByShaAndRepositoryId')
            ->andThrow(new FailedToCheckIfCommitExistsException('Failed to check if commit exists'));

        // Ensure the create method is not called
        $commitRepositoryMock->shouldNotReceive('create');

        // Create an instance of the job
        $job = new ProcessCommit($repositoryDto, $commitData);

        // Call the handle method
        $job->handle($commitRepositoryMock);

        // Assert that the CommitCreated event was not dispatched
        Event::assertNotDispatched(CommitCreated::class);
    }

    public function test_exists_by_sha_and_repository_id_returns_true_if_commit_exists()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        // Create a commit with a specific sha and repository_id
        Commit::factory()->create([
            'sha' => '123456',
            'repository_id' => 1,
        ]);

        // Instantiate the CommitRepository
        $commitRepository = new CommitRepository();

        // Call the method and assert it returns true
        $exists = $commitRepository->existsByShaAndRepositoryId('123456', 1);

        $this->assertTrue($exists);
    }

    public function test_exists_by_sha_and_repository_id_returns_false_if_commit_does_not_exist()
    {
        // Instantiate the CommitRepository
        $commitRepository = new CommitRepository();

        // Call the method and assert it returns false
        $exists = $commitRepository->existsByShaAndRepositoryId('123456', 1);

        $this->assertFalse($exists);
    }

    public function test_exists_by_sha_and_repository_id_throws_exception_on_error()
    {
        // Drop the commits table to simulate an exception
        Commit::query()->getConnection()->getSchemaBuilder()->drop('commits');

        // Instantiate the CommitRepository
        $commitRepository = new CommitRepository();

        // Assert that the method throws a FailedToCheckIfCommitExistsException
        $this->expectException(FailedToCheckIfCommitExistsException::class);

        $commitRepository->existsByShaAndRepositoryId('123456', 1);
    }
}

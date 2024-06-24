<?php

namespace Modules\Commit\tests\Actions;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Commit\database\repository\CommitRepository;
use Modules\Commit\database\repository\CommitRepositoryInterface;
use Modules\Commit\src\DTOs\CreateCommitDetails;
use Modules\Commit\src\Exceptions\CommitUpdateOrCreateFailedException;
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
            return $job->createCommitDetails->sha === '123456';
        });

        Queue::assertPushed(ProcessCommit::class, function ($job) {
            return $job->createCommitDetails->sha === '789012';
        });

        Queue::assertPushed(ProcessCommit::class, 2);
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

    public function test_handle_method_calls_update_or_create_on_commit_repository()
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

        // Mock the CommitRepositoryInterface
        $commitRepositoryMock = Mockery::mock(CommitRepositoryInterface::class);
        $commitRepositoryMock->shouldReceive('updateOrCreate')
            ->once()
            ->with($createCommitDetails);

        // Create an instance of the job
        $job = new ProcessCommit($createCommitDetails);

        // Call the handle method
        $job->handle($commitRepositoryMock);
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
        $commitDto = $repository->updateOrCreate($createCommitDetails);

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
        $commitDto = $repository->updateOrCreate($createCommitDetails);

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
        $repository->updateOrCreate($createCommitDetails);
    }
}

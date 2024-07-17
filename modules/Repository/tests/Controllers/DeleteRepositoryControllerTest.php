<?php

namespace Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Commit\src\Events\CommitDeleted;
use Modules\Commit\src\Listeners\DeleteCommitFiles;
use Modules\Commit\src\Listeners\DeleteCommits;
use Modules\Commit\src\Models\Commit;
use Modules\Commit\src\Models\CommitFile;
use Modules\Repository\database\repository\RepositoryRepository;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Events\RepositoryDeleted;
use Modules\Repository\src\Exceptions\RepositoryDeletionFailedException;
use Modules\Repository\src\Exceptions\RepositoryInfoFindFailedException;
use Modules\Repository\src\Listeners\DeleteBranches;
use Modules\Repository\src\Models\Branch;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Modules\User\src\Listeners\DeleteCollaborators;
use Modules\User\src\Models\User;
use Nette\Schema\Schema;
use Tests\TestCase;

class DeleteRepositoryControllerTest extends TestCase
{
    use RefreshDatabase;
    public function test_delete_repository_success()
    {
        Event::fake();
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $repositoryDto = RepositoryDto::fromEloquent($repository);

        $repositoryMock = Mockery::mock(RepositoryRepositoryInterface::class);
        $this->app->instance(RepositoryRepositoryInterface::class, $repositoryMock);

        $repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($repository->id)
            ->andReturn($repositoryDto);

        $repositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($repository->id)
            ->andReturn(true);

        $response = $this->postJson(route('repository.delete', ['id' => $repository->id]));

        $response->assertStatus(200)->assertJson(['data' => []]);

        Event::assertDispatched(RepositoryDeleted::class, function ($event) use ($repositoryDto) {
            return $event->repository->id === $repositoryDto->id;
        });
    }
    public function test_delete_repository_info_find_failed()
    {
        $repositoryId = 1;
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $repositoryMock = Mockery::mock(RepositoryRepositoryInterface::class);
        $this->app->instance(RepositoryRepositoryInterface::class, $repositoryMock);

        $repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($repositoryId)
            ->andThrow(new RepositoryInfoFindFailedException());

        $response = $this->postJson(route('repository.delete', ['id' => $repositoryId]));

        $response->assertStatus(500);
    }

    public function test_delete_repository_deletion_failed()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $repositoryDto = RepositoryDto::fromEloquent($repository);

        $repositoryMock = Mockery::mock(RepositoryRepositoryInterface::class);
        $this->app->instance(RepositoryRepositoryInterface::class, $repositoryMock);

        $repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($repository->id)
            ->andReturn($repositoryDto);

        $repositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($repository->id)
            ->andThrow(new RepositoryDeletionFailedException());

        $response = $this->postJson(route('repository.delete', ['id' => $repository->id]));

        $response->assertStatus(500);
    }

    public function test_find_by_id_success()
    {
        $repositoryRepository = new RepositoryRepository();
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();

        $result = $repositoryRepository->findById($repository->id);

        $this->assertInstanceOf(RepositoryDto::class, $result);
        $this->assertEquals($repository->id, $result->id);
    }

    public function test_find_by_id_failure()
    {
        $repositoryRepository = new RepositoryRepository();
        $this->expectException(RepositoryInfoFindFailedException::class);
        \Illuminate\Support\Facades\Schema::drop('repositories');
        $repositoryRepository->findById(9999); // Non-existing ID
    }
    public function test_delete_repository_success_within_repositoryInterface()
    {
        $repositoryRepository = new RepositoryRepository();
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();

        $result = $repositoryRepository->delete($repository->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('repositories', ['id' => $repository->id]);
    }

    public function test_delete_repository_failure()
    {
        $repositoryRepository = new RepositoryRepository();
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();

        \Illuminate\Support\Facades\Schema::drop('repositories');

        $this->expectException(RepositoryDeletionFailedException::class);

        $repositoryRepository->delete($repository->id);
    }

    public function test_delete_collaborators_success()
    {
        // Create a repository and collaborators
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $collaborators = User::factory()->count(3)->create(['repository_id' => $repository->id]);

        // Ensure collaborators are in the database
        $this->assertDatabaseCount('users', 3);
        foreach ($collaborators as $collaborator) {
            $this->assertDatabaseHas('users', ['id' => $collaborator->id]);
        }

        // Create the RepositoryDeleted event
        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $event = new RepositoryDeleted($repositoryDto);

        // Instantiate and handle the listener
        $listener = new DeleteCollaborators();
        $listener->handle($event);

        // Assert collaborators are deleted
        foreach ($collaborators as $collaborator) {
            $this->assertDatabaseMissing('users', ['id' => $collaborator->id]);
        }
    }

    public function test_delete_collaborators_handles_exceptions()
    {
        $githubToken = GithubToken::factory()->create();
        // Create a repository without collaborators
        $repository = Repository::factory()->create();

        // Create the RepositoryDeleted event
        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $event = new RepositoryDeleted($repositoryDto);

        \Illuminate\Support\Facades\Schema::drop('users');
        // Fake the Log facade
        Log::shouldReceive('error')->once();

        // Instantiate and handle the listener
        $listener = new DeleteCollaborators();
        $listener->handle($event);
    }

    public function test_delete_branches_success()
    {
        $githubToken = GithubToken::factory()->create();
        // Create a repository and branches
        $repository = Repository::factory()->create();
        $branches = Branch::factory()->count(3)->create(['repository_id' => $repository->id]);

        // Ensure branches are in the database
        $this->assertDatabaseCount('branches', 3);
        foreach ($branches as $branch) {
            $this->assertDatabaseHas('branches', ['id' => $branch->id]);
        }

        // Create the RepositoryDeleted event
        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $event = new RepositoryDeleted($repositoryDto);

        // Instantiate and handle the listener
        $listener = new DeleteBranches();
        $listener->handle($event);

        // Assert branches are deleted
        foreach ($branches as $branch) {
            $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
        }
    }

    public function test_delete_branches_handles_exceptions()
    {
        $githubToken = GithubToken::factory()->create();
        // Create a repository without branches
        $repository = Repository::factory()->create();

        // Create the RepositoryDeleted event
        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $event = new RepositoryDeleted($repositoryDto);

        \Illuminate\Support\Facades\Schema::drop('branches');

        // Fake the Log facade
        Log::shouldReceive('error')->once();

        // Instantiate and handle the listener
        $listener = new DeleteBranches();
        $listener->handle($event);
    }

    public function test_delete_commits_success()
    {
        Event::fake();
        $githubToken = GithubToken::factory()->create();
        // Create a repository and commits
        $repository = Repository::factory()->create();
        $commits = Commit::factory()->count(3)->create(['repository_id' => $repository->id]);

        // Ensure commits are in the database
        $this->assertDatabaseCount('commits', 3);
        foreach ($commits as $commit) {
            $this->assertDatabaseHas('commits', ['id' => $commit->id]);
        }

        // Create the RepositoryDeleted event
        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $event = new RepositoryDeleted($repositoryDto);

        // Instantiate and handle the listener
        $listener = new DeleteCommits();
        $listener->handle($event);

        // Assert commits are deleted and CommitDeleted event is dispatched
        foreach ($commits as $commit) {
            $this->assertDatabaseMissing('commits', ['id' => $commit->id]);
            Event::assertDispatched(CommitDeleted::class);
        }
    }
    public function test_delete_commits_handles_exceptions()
    {
        Event::fake();
        Log::shouldReceive('error')->once();
        $githubToken = GithubToken::factory()->create();
        // Create a repository
        $repository = Repository::factory()->create();

        // Create the RepositoryDeleted event
        $repositoryDto = RepositoryDto::fromEloquent($repository);
        $event = new RepositoryDeleted($repositoryDto);

        \Illuminate\Support\Facades\Schema::drop('commits');

        // Instantiate and handle the listener
        $listener = new DeleteCommits();
        $listener->handle($event);

        Event::assertNotDispatched(CommitDeleted::class);
    }

    public function test_delete_commit_files_success()
    {
        $githubToken = GithubToken::factory()->create();
        // Create a repository
        $repository = Repository::factory()->create();
        // Create a commit and commit files
        $commit = Commit::factory()->create();
        $commitFiles = CommitFile::factory()->count(3)->create(['commit_id' => $commit->id]);

        // Ensure commit files are in the database
        $this->assertDatabaseCount('commit_files', 3);
        foreach ($commitFiles as $commitFile) {
            $this->assertDatabaseHas('commit_files', ['id' => $commitFile->id]);
        }

        // Create the CommitDeleted event
        $event = new CommitDeleted($commit);

        // Instantiate and handle the listener
        $listener = new DeleteCommitFiles();
        $listener->handle($event);

        // Assert commit files are deleted
        foreach ($commitFiles as $commitFile) {
            $this->assertDatabaseMissing('commit_files', ['id' => $commitFile->id]);
        }

    }

    public function test_delete_commit_files_handles_exceptions()
    {
        Log::shouldReceive('error')->once();

        $githubToken = GithubToken::factory()->create();
        // Create a repository
        $repository = Repository::factory()->create();
        // Create a commit without commit files
        $commit = Commit::factory()->create();

        // Create the CommitDeleted event
        $event = new CommitDeleted($commit);

        \Illuminate\Support\Facades\Schema::drop('commit_files');

        // Instantiate and handle the listener
        $listener = new DeleteCommitFiles();
        $listener->handle($event);
    }
}

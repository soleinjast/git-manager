<?php

namespace Modules\User\tests\Actions;

use App\Services\GithubService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Events\RepositoryCreated;
use Mockery;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Modules\User\database\repository\UserRepository;
use Modules\User\database\repository\UserRepositoryInterface;
use Modules\User\src\DTOs\UserCreateDetails;
use Modules\User\src\Jobs\CreateUser;
use Modules\User\src\Listeners\StoreCollaborators;
use Modules\User\src\Models\User;
use Nette\Schema\Schema;
use Tests\TestCase;

class StoreCollaboratorsTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_handles_repository_created_event_and_dispatches_jobs()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-name/collaborators' => Http::response([
                [
                    'login' => 'collaborator1',
                    'id' => 1,
                    'avatar_url' => 'http://example.com/avatar1.png',
                ],
                [
                    'login' => 'collaborator2',
                    'id' => 2,
                    'avatar_url' => 'http://example.com/avatar2.png',
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
        $event = new RepositoryCreated($repositoryDto);
        $listener = new StoreCollaborators();
        $listener->handle($event);

        Queue::assertPushed(CreateUser::class, function ($job) use ($repositoryDto) {
            return $job->repository->id === $repositoryDto->id;
        });

        Queue::assertPushed(CreateUser::class, 2);
    }

    public function test_handle_user_creation_or_update()
    {
        $githubToken = GithubToken::factory()->create();

        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);

        $repositoryDto = RepositoryDto::fromEloquent($repository);

        $userData = [
            'login' => 'collaborator1',
            'name' => 'Collaborator One',
            'id' => 1,
            'avatar_url' => 'https://example.com/avatar1.png',
            'university_username' => 'testuser',
            'status' => 'approved'
        ];

        $job = new CreateUser($repositoryDto, $userData);

        $userRepositoryMock = Mockery::mock(UserRepositoryInterface::class);
        $userRepositoryMock->shouldReceive('updateOrCreate')
            ->once()
            ->andReturnNull();

        $this->app->instance(UserRepositoryInterface::class, $userRepositoryMock);

        $job->handle($userRepositoryMock);
    }

    public function test_handle_user_creation_or_update_with_exist_user_on_job()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);
        User::factory()->create([
            'repository_id' => $repository->id,
            'login_name' => 'collaborator1',
            'name' => 'Collaborator One',
            'git_id' => 1,
            'avatar_url' => 'https://example.com/avatar1.png',
            'university_username' => 'testusername',
            'status' => 'approved'
        ]);

        $repositoryDto = RepositoryDto::fromEloquent($repository);

        $userData = [
            'login' => 'collaborator1',
            'name' => 'Collaborator One',
            'id' => 1,
            'avatar_url' => 'https://example.com/avatar1.png',
            'university_username' => '',
            'status' => 'approved'
        ];

        $job = new CreateUser($repositoryDto, $userData);

        $userRepositoryMock = Mockery::mock(UserRepositoryInterface::class);
        $userRepositoryMock->shouldReceive('updateOrCreate')
            ->once()
            ->andReturnNull();

        $this->app->instance(UserRepositoryInterface::class, $userRepositoryMock);

        $job->handle($userRepositoryMock);
    }
    public function test_update_or_create_creates_a_new_user()
    {
        $githubToken = GithubToken::factory()->create();

        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);
        $userCreateDetails = new UserCreateDetails(
            repositoryId: $repository->id,
            login_name: 'testuser',
            name: 'Test User',
            git_id: 123456,
            avatar_url: 'https://example.com/avatar.png',
            university_username: 'testuser',
            status: 'approved'
        );

        $userRepository = new UserRepository();
        $userRepository->updateOrCreate($userCreateDetails);

        $this->assertDatabaseHas('users', [
            'repository_id' => 1,
            'login_name' => 'testuser',
            'name' => 'Test User',
            'git_id' => 123456,
            'avatar_url' => 'https://example.com/avatar.png'
        ]);
    }

    public function test_update_or_create_updates_an_existing_user()
    {
        $githubToken = GithubToken::factory()->create();

        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);
        // Create an existing user
        $existingUser = User::factory()->create([
            'repository_id' => 1,
            'login_name' => 'testuser',
            'name' => 'Old Name',
            'git_id' => 123456,
            'avatar_url' => 'http://example.com/old_avatar.png',
            'university_username' => 'testuser',
            'status' => 'approved'
        ]);

        $userCreateDetails = new UserCreateDetails(
            repositoryId: 1,
            login_name: 'testuser2',
            name: 'New Name',
            git_id: 123456,
            avatar_url: 'http://example.com/new_avatar.png',
            university_username: 'testuser',
            status: 'approved'
        );

        $userRepository = new UserRepository();
        $userRepository->updateOrCreate($userCreateDetails);

        $this->assertDatabaseHas('users', [
            'repository_id' => 1,
            'login_name' => 'testuser2',
            'name' => 'New Name',
            'git_id' => 123456,
            'avatar_url' => 'http://example.com/new_avatar.png'
        ]);

        $this->assertDatabaseMissing('users', [
            'repository_id' => 1,
            'login_name' => 'testuser',
            'name' => 'Old Name',
            'git_id' => 123456,
            'avatar_url' => 'http://example.com/old_avatar.png'
        ]);
    }

    public function test_update_or_create_handles_exceptions()
    {
        $githubToken = GithubToken::factory()->create();

        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $githubToken->id,
            'deadline' => now()->addDays(1),
        ]);
        $this->expectException(Exception::class);

        $userCreateDetails = new UserCreateDetails(
            repositoryId: $repository->id,
            login_name: 'testuser',
            name: 'Test User',
            git_id: 123456,
            avatar_url: 'http://example.com/avatar.png',
            university_username: 'testuser',
            status: 'approved'
        );

        \Illuminate\Support\Facades\Schema::drop('users');

        $userRepository = new UserRepository();
        $userRepository->updateOrCreate($userCreateDetails);
    }

    public function testGetCollaboratorsHandlesConnectionException()
    {
        $service = new GithubService();
        $service->setModel('fake-token', 'test-owner', 'test-repo');

        Http::fake(function () {
            throw new ConnectionException('Connection error');
        });

        $result = $service->getCollaborators();

        $this->assertEmpty($result);
        // Ensure the error is logged
        $this->assertLogged('error', 'Connection error');
    }

    public function testGetCollaboratorsHandlesServerError()
    {
        $service = new GithubService();
        $service->setModel('fake-token', 'test-owner', 'test-repo');

        Http::fake([
            'https://api.github.com/repos/test-owner/test-repo/collaborators' => Http::response([], 500)
        ]);

        $result = $service->getCollaborators();

        $this->assertEmpty($result);
    }

    public function testGetUserDetailsHandlesConnectionException()
    {
        $service = new GithubService();
        $service->setModel('fake-token', 'test-owner', 'test-repo');

        Http::fake(function () {
            throw new ConnectionException('Connection error');
        });

        $result = $service->getUserDetails('test-user');

        $this->assertEmpty($result);
        // Ensure the error is logged
        $this->assertLogged('error', 'Connection error');
    }

    public function testGetUserDetailsHandlesServerError()
    {
        $service = new GithubService();
        $service->setModel('fake-token', 'test-owner', 'test-repo');

        Http::fake([
            'https://api.github.com/users/test-user' => Http::response([], 500)
        ]);

        $result = $service->getUserDetails('test-user');

        $this->assertEmpty($result);
    }

    private function assertLogged($level, $message): void
    {
        // This is a simple method to ensure that the error is logged.
        // You can expand this to use a more sophisticated logging assertion.
        $logContent = file_get_contents(storage_path('logs/laravel.log'));
        $this->assertStringContainsString($message, $logContent);
    }

}

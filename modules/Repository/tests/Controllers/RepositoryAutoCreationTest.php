<?php

namespace Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

use GuzzleHttp\Promise\PromiseInterface;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\DTOs\CreateRepositoryDetails;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Events\RepositoriesCreationOnGithubCreated;
use Modules\Repository\src\Events\RepositoriesCreationOnGithubInitiated;
use Modules\Repository\src\Exceptions\RepositoryCreationFailedException;
use Modules\Repository\src\Listeners\CreateRepositoryOnGit;
use Modules\Repository\src\Listeners\StoreRepositoryAndCollaboratorsOnLocal;
use Modules\Repository\src\Middleware\PrepareRequestForAutoCreation;
use Modules\Repository\src\Middleware\ValidateGithubOrganizationAccess;
use Modules\Repository\src\Middleware\ValidateGitHubUsernames;
use Modules\Token\src\Models\GithubToken;
use Modules\User\database\repository\UserRepositoryInterface;
use Tests\TestCase;

class RepositoryAutoCreationTest extends TestCase
{
    use RefreshDatabase;
    public function test_auto_create_repository_success()
    {
        Event::fake();
        // Fake the GitHub API responses
        Http::fake([
            'https://api.github.com/users/user1' => Http::response([
                'id' => 101,
                'login' => 'user1',
                'name' => 'User One',
                'avatar_url' => 'https://example.com/avatar1.png'
            ], 200),
            'https://api.github.com/users/user2' => Http::response([
                'id' => 102,
                'login' => 'user2',
                'name' => 'User Two',
                'avatar_url' => 'https://example.com/avatar2.png'
            ], 200),
            'https://api.github.com/orgs/orgname/memberships/token-user' => Http::response([
                'state' => 'active',
                'role' => 'admin',
            ], 200),
        ]);

        // Create a GitHub token
        $githubToken = GithubToken::factory()->create([
            'token' => 'test-token',
            'login_name' => 'token-user'
        ]);

        // Mock the GuzzleHttp\Client
        $clientMock = Mockery::mock(Client::class);
        $clientMock->shouldReceive('getAsync')
            ->with('https://api.github.com/users/user1', Mockery::type('array'))
            ->andReturn(new FulfilledPromise(new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'id' => 101,
                'login' => 'user1',
                'name' => 'User One',
                'avatar_url' => 'https://example.com/avatar1.png'
            ]))));

        $clientMock->shouldReceive('getAsync')
            ->with('https://api.github.com/users/user2', Mockery::type('array'))
            ->andReturn(new FulfilledPromise(new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'id' => 102,
                'login' => 'user2',
                'name' => 'User Two',
                'avatar_url' => 'https://example.com/avatar2.png'
            ]))));

        // Bind the mock to the container
        $this->instance(Client::class, $clientMock);

        // Make the POST request with the valid input
        $response = $this->postJson(route('repository.auto-create'), [
            'group_count' => 1,
            'members_per_group' => 2,
            'token_id' => $githubToken->id,
            'organization' => 'orgname',
            'deadline' => now()->addDays(1)->toDateString(),
            'members' => [
                ['github_username' => 'user1', 'university_username' => 'uuser1'],
                ['github_username' => 'user2', 'university_username' => 'uuser2']
            ]
        ]);
        Event::assertDispatched(RepositoriesCreationOnGithubInitiated::class, 1);
        Event::assertDispatched(RepositoriesCreationOnGithubInitiated::class, function ($event) {
            return $event->repositoryDetails->owner === 'orgname' &&
                $event->repositoryDetails->deadline === now()->addDays(1)->toDateString() &&
                $event->githubToken->id === 1 &&
                $event->githubToken->token === 'test-token' &&
                $event->githubToken->login_name === 'token-user';
        });
        $response->assertStatus(200);
    }
    public function test_valid_request_passes_middleware()
    {
        $githubToken = GithubToken::factory()->create();
        $middleware = new PrepareRequestForAutoCreation();

        $request = Request::create('/test', 'POST', [
            'group_count' => 2,
            'members_per_group' => 3,
            'token_id' => 1,
            'organization' => 'organization_name',
            'deadline' => now()->addDays(1)->toDateString(),
            'members' => [
                ['github_username' => 'valid_user1'],
                ['github_username' => 'valid_user2'],
                ['github_username' => 'valid_user3'],
                ['github_username' => 'valid_user4'],
                ['github_username' => 'valid_user5'],
                ['github_username' => 'valid_user6'],
            ],
        ]);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });


        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertEquals(['success' => true], $response->getData(true));
    }
    public function test_invalid_request_fails_due_to_missing_fields()
    {
        $middleware = new PrepareRequestForAutoCreation();

        $request = Request::create('/test', 'POST', [
            // Missing group_count, members_per_group, token_id, owner, organization, deadline, members
        ]);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->status());
        $errors = $response->getData(true)['errors'];
        $this->assertCount(6, $errors); // Since we have 7 required fields
    }
    public function test_invalid_request_fails_due_to_insufficient_members()
    {
        $githubToken = GithubToken::factory()->create();
        $middleware = new PrepareRequestForAutoCreation();

        $request = Request::create('/test', 'POST', [
            'group_count' => 2,
            'members_per_group' => 3,
            'token_id' => 1,
            'organization' => 'organization_name',
            'deadline' => now()->addDays(1)->toDateString(),
            'members' => [
                ['github_username' => 'valid_user1'],
                ['github_username' => 'valid_user2'],
                ['github_username' => 'valid_user3'],
            ],
        ]);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->status());
        $errors = $response->getData(true)['errors'];
        $this->assertCount(1, $errors); // Insufficient members error
    }
    public function test_invalid_request_fails_due_to_duplicate_github_usernames()
    {
        $githubToken = GithubToken::factory()->create();
        $middleware = new PrepareRequestForAutoCreation();

        $request = Request::create('/test', 'POST', [
            'group_count' => 2,
            'members_per_group' => 3,
            'token_id' => 1,
            'organization' => 'organization_name',
            'deadline' => now()->addDays(1)->toDateString(),
            'members' => [
                ['github_username' => 'duplicate_user'],
                ['github_username' => 'duplicate_user'],
                ['github_username' => 'valid_user3'],
                ['github_username' => 'valid_user4'],
                ['github_username' => 'valid_user5'],
                ['github_username' => 'valid_user6'],
            ],
        ]);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->status());
        $errors = $response->getData(true)['errors'];
        $this->assertCount(1, $errors); // Duplicate GitHub usernames error
    }
    public function test_valid_input_including_university_usernames()
    {
        // Fake the GitHub API responses
        Http::fake([
            'https://api.github.com/users/user1' => Http::response([
                'id' => 101,
                'login' => 'user1',
                'name' => 'User One',
                'avatar_url' => 'https://example.com/avatar1.png'
            ], 200),
            'https://api.github.com/users/user2' => Http::response([
                'id' => 102,
                'login' => 'user2',
                'name' => 'User Two',
                'avatar_url' => 'https://example.com/avatar2.png'
            ], 200),
            'https://api.github.com/orgs/orgname/memberships/token-user' => Http::response([
                'state' => 'active',
                'role' => 'admin',
            ], 200),
        ]);

        // Create a GitHub token
        $githubToken = GithubToken::factory()->create([
            'token' => 'test-token',
            'login_name' => 'token-user'
        ]);

        // Mock the GuzzleHttp\Client
        $clientMock = Mockery::mock(Client::class);
        $clientMock->shouldReceive('getAsync')
            ->with('https://api.github.com/users/user1', Mockery::type('array'))
            ->andReturn(new FulfilledPromise(new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'id' => 101,
                'login' => 'user1',
                'name' => 'User One',
                'avatar_url' => 'https://example.com/avatar1.png'
            ]))));

        $clientMock->shouldReceive('getAsync')
            ->with('https://api.github.com/users/user2', Mockery::type('array'))
            ->andReturn(new FulfilledPromise(new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'id' => 102,
                'login' => 'user2',
                'name' => 'User Two',
                'avatar_url' => 'https://example.com/avatar2.png'
            ]))));

        // Bind the mock to the container
        $this->instance(Client::class, $clientMock);

        // Create the middleware instance with the mocked client
        $middleware = new ValidateGitHubUsernames($clientMock);

        // Make the POST request with the valid input
        $request = Request::create(route('repository.auto-create'), 'POST', [
            'group_count' => 1,
            'members_per_group' => 2,
            'token_id' => $githubToken->id,
            'organization' => 'orgname',
            'deadline' => now()->addDays(1)->toDateString(),
            'members' => [
                ['github_username' => 'user1', 'university_username' => 'uuser1'],
                ['github_username' => 'user2', 'university_username' => 'uuser2']
            ]
        ]);

        // Pass the request through the middleware
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });
        // Assert the response is OK
        $this->assertEquals(200, $response->status());
    }
    public function test_rejects_request_with_duplicate_university_usernames()
    {
        $githubToken = GithubToken::factory()->create();
        $response = $this->postJson(route('repository.auto-create'), [
            'group_count' => 1,
            'members_per_group' => 2,
            'token_id' => 1,
            'organization' => 'orgname',
            'deadline' => now()->addDays(1)->toDateString(),
            'members' => [
                ['github_username' => 'user1', 'university_username' => 'uuser1'],
                ['github_username' => 'user2', 'university_username' => 'uuser1']
            ]
        ]);
        $this->assertEquals(422, $response->status());
        $response->assertJson(fn(AssertableJson $json) =>
        $json->where('success', false)
            ->where('message', 'Operation failed!')
            ->has('errors', 1)
            ->where('errors.0.field', 'members')
            ->where('errors.0.message', 'The members field has duplicate university usernames.')
        );
    }
    public function test_accepts_request_with_unique_university_usernames()
    {
        $githubToken = GithubToken::factory()->create();
        $middleware = new PrepareRequestForAutoCreation();

        $request = Request::create('/api/auto-create', 'POST', [
            'group_count' => 1,
            'members_per_group' => 2,
            'members' => [
                ['github_username' => 'user1', 'university_username' => 'uni1'],
                ['github_username' => 'user2', 'university_username' => 'uni2'],
            ],
            'token_id' => 1,
            'organization' => 'test-org',
            'deadline' => now()->addDays(10)->format('Y-m-d')
        ]);

        $response = $middleware->handle($request, function () {
            return response()->json(['message' => 'Success']);
        });

        $this->assertEquals(200, $response->status());
        $this->assertStringContainsString('Success', $response->getContent());
    }
    public function test_valid_member_fields()
    {
        $githubToken = GithubToken::factory()->create();
        $response = $this->postJson(route('repository.auto-create'), [
            'group_count' => 1,
            'members_per_group' => 2,
            'token_id' => 1,
            'organization' => 'orgname',
            'deadline' => now()->addDays(1)->toDateString(),
            'members' => [
                ['university_username' => 'uuser1'],
                ['github_username' => 'user2', 'university_username' => 'uuser2']
            ]
        ]);
        $this->assertEquals(422, $response->status());
        $response->assertJson(fn(AssertableJson $json) =>
        $json->where('success', false)
            ->where('message', 'Operation failed!')
            ->has('errors', 1)
            ->where('errors.0.field', 'members')
            ->where('errors.0.message', 'A github username missed! Each member must have a github username.')
        );
    }
    public function test_deadline_must_be_in_future()
    {
        $githubToken = GithubToken::factory()->create();
        $response = $this->postJson(route('repository.auto-create'), [
            'group_count' => 1,
            'members_per_group' => 2,
            'token_id' => 1,
            'organization' => 'orgname',
            'deadline' => now()->subDays(1)->toDateString(),
            'members' => [
                ['github_username' => 'user1', 'university_username' => 'uuser1'],
                ['github_username' => 'user2', 'university_username' => 'uuser2']
            ]
        ]);
        $this->assertEquals(422, $response->status());
        $response->assertJson(fn(AssertableJson $json) =>
        $json->where('success', false)
            ->where('message', 'Operation failed!')
            ->has('errors', 1)
            ->where('errors.0.field', 'deadline')
            ->where('errors.0.message', 'The deadline field must be a date after today.')
        );
    }
    public function test_user_has_permission_to_create_repositories()
    {
        $token = GithubToken::factory()->create(['login_name' => 'test-user']);
        $organization = 'test-org';

        Http::fake([
            "https://api.github.com/orgs/{$organization}/memberships/{$token->login_name}" => Http::response([
                'state' => 'active',
                'role' => 'admin',
            ], 200),
        ]);

        // Create the request
        $request = Request::create('/test', 'POST', [
            'token_id' => $token->id,
            'organization' => $organization,
        ]);
        $middleware = new ValidateGithubOrganizationAccess();
        $response = $middleware->handle($request, fn() => response()->json(['success' => true]));

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->getData(true)['success']);
    }
    public function test_user_does_not_have_permission_to_create_repositories()
    {
        $token = GithubToken::factory()->create();
        $username = 'test-user';
        $organization = 'test-org';

        Http::fake([
            "https://api.github.com/orgs/{$organization}/memberships/{$username}" => Http::response([
                'state' => 'active',
                'role' => 'member',
            ], 200),
        ]);

        // Create the request
        $request = Request::create('/test', 'POST', [
            'token_id' => $token->id,
            'organization' => $organization,
        ]);
        $request->setUserResolver(fn() => (object)['username' => $username]);

        // Create middleware instance and call handle method
        $middleware = new ValidateGithubOrganizationAccess();
        $response = $middleware->handle($request, fn() => response()->json(['success' => true]));

        $this->assertEquals(403, $response->status());
        $this->assertFalse($response->getData(true)['success']);
        $this->assertEquals('You do not have permission to create repositories in this organization.', $response->getData(true)['message']);
    }
    public function test_access_exception_handled_properly()
    {
        $token = GithubToken::factory()->create();
        $username = 'test-user';
        $organization = 'test-org';

        Http::fake([
            "https://api.github.com/orgs/{$organization}/memberships/{$username}" => Http::response([], 500),
        ]);

        // Create the request
        $request = Request::create('/test', 'POST', [
            'token_id' => $token->id,
            'organization' => $organization,
        ]);
        $request->setUserResolver(fn() => (object)['username' => $username]);

        // Create middleware instance and call handle method
        $middleware = new ValidateGithubOrganizationAccess();
        $response = $middleware->handle($request, fn() => response()->json(['success' => true]));

        $this->assertEquals(403, $response->status());
        $this->assertFalse($response->getData(true)['success']);
        $this->assertEquals('You do not have permission to create repositories in this organization.', $response->getData(true)['message']);
    }
    public function test_connection_exception_handled_properly()
    {
            $token = GithubToken::factory()->create([
                'login_name' => 'test-user',
                'token' => 'test-token',
            ]);
            $organization = 'test-org';

            Http::fake([
                "https://api.github.com/orgs/{$organization}/memberships/{$token->login_name}" => function () {
                    throw new ConnectionException('Connection failed');
                },
            ]);

            // Create the request
            $request = Request::create('/test', 'POST', [
                'token_id' => $token->id,
                'organization' => $organization,
            ]);
            // Create middleware instance and call handle method
            $middleware = new ValidateGithubOrganizationAccess();
            $response = $middleware->handle($request, fn() => response()->json(['success' => true]));

            $this->assertEquals(500, $response->status());
            $this->assertFalse($response->getData(true)['success']);
            $this->assertEquals('Failed to connect to Github API.', $response->getData(true)['message']);
    }
    public function test_valid_github_usernames_passes()
    {
        // Fake the GitHub API responses
        Http::fake([
            'https://api.github.com/users/validuser1' => Http::response([
                'id' => 101,
                'login' => 'validuser1',
                'name' => 'User One',
                'avatar_url' => 'https://example.com/avatar1.png'
            ], 200),
            'https://api.github.com/users/validuser2' => Http::response([
                'id' => 102,
                'login' => 'validuser2',
                'name' => 'User Two',
                'avatar_url' => 'https://example.com/avatar2.png'
            ], 200),
        ]);

        // Create a GitHub token
        $token = GithubToken::factory()->create([
            'token' => 'test-token',
            'login_name' => 'token-user'
        ]);

        // Mock the GuzzleHttp\Client
        $clientMock = Mockery::mock(Client::class);
        $clientMock->shouldReceive('getAsync')
            ->with('https://api.github.com/users/validuser1', Mockery::type('array'))
            ->andReturn(new FulfilledPromise(new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'id' => 101,
                'login' => 'validuser1',
                'name' => 'User One',
                'avatar_url' => 'https://example.com/avatar1.png'
            ]))));
        $clientMock->shouldReceive('getAsync')
            ->with('https://api.github.com/users/validuser2', Mockery::type('array'))
            ->andReturn(new FulfilledPromise(new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'id' => 102,
                'login' => 'validuser2',
                'name' => 'User Two',
                'avatar_url' => 'https://example.com/avatar2.png'
            ]))));

        // Bind the mock to the container
        $this->instance(Client::class, $clientMock);

        // Create the middleware instance with the mocked client
        $middleware = new ValidateGitHubUsernames($clientMock);

        // Create the request
        $request = Request::create('/test', 'POST', [
            'token_id' => $token->id,
            'members' => [
                ['github_username' => 'validuser1'],
                ['github_username' => 'validuser2'],
            ],
        ]);

        // Pass the request through the middleware
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // Assert the response is OK
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->getData(true)['success']);
    }
    public function test_invalid_github_usernames_fails()
    {
        $token = GithubToken::factory()->create();

        Http::fake([
            'https://api.github.com/users/invaliduser' => Http::response([], 404),
        ]);

        $request = Request::create('/test', 'POST', [
            'token_id' => $token->id,
            'members' => [
                ['github_username' => 'invaliduser'],
            ],
        ]);

        $middleware = new ValidateGitHubUsernames(new Client());
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->status());
        $this->assertFalse($response->getData(true)['success']);
        $this->assertEquals("Invalid GitHub username: invaliduser", $response->getData(true)['message']);
    }
    public function test_connection_exception_handled_properly_while_validating_github_usernames()
    {
        $token = GithubToken::factory()->create();

        Http::fake([
            'https://api.github.com/users/validuser' => function () {
                throw new \Exception('Connection failed');
            },
        ]);

        $request = Request::create('/test', 'POST', [
            'token_id' => $token->id,
            'members' => [
                ['github_username' => 'validuser'],
            ],
        ]);

        $middleware = new ValidateGitHubUsernames(new Client());
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->status());
        $this->assertFalse($response->getData(true)['success']);
        $this->assertEquals("Invalid GitHub username: validuser", $response->getData(true)['message']);
    }
    public function test_handle_creates_repository_and_adds_collaborators()
    {
        Queue::fake();
        Event::fake();
        $githubToken = GithubToken::factory()->create(['token' => 'test-token']);
        $repositoryDetails = new CreateRepositoryDetails(
            1, 'test-org', 'test-repo', now()->addDay(1)->toDateString()
        );
        $members = [
            ['github_username' => 'user1', 'university_username' => 'uuser1'],
            ['github_username' => 'user2', 'university_username' => 'uuser2']
        ];
        Http::fake([
            'https://api.github.com/orgs/test-org/repos' => Http::response(['id' => 1, 'name' => 'test-repo'], 201),
            'https://api.github.com/repos/test-org/test-repo/collaborators/user1' => Http::response([], 201),
            'https://api.github.com/repos/test-org/test-repo/collaborators/user2' => Http::response([], 201),
        ]);

        $listener = new CreateRepositoryOnGit(Event::getFacadeRoot());
        $listener->handle(new RepositoriesCreationOnGithubInitiated($repositoryDetails, $members, $githubToken));

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.github.com/orgs/test-org/repos' && $request['name'] == 'test-repo';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.github.com/repos/test-org/test-repo/collaborators/user1';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.github.com/repos/test-org/test-repo/collaborators/user2';
        });

        Event::assertDispatched(RepositoriesCreationOnGithubCreated::class, function ($event) use ($repositoryDetails, $members, $githubToken) {
            return $event->repositoryDetails == $repositoryDetails &&
                $event->members == $members &&
                $event->githubToken->id == $githubToken->id;
        });
    }
    public function test_handle_reports_connection_exception()
    {
        Queue::fake();
        Event::fake();

        $repositoryDetails = new CreateRepositoryDetails(
            1, 'test-org', 'test-repo', now()->addDay()->toDateString()
        );
        $members = [
            ['github_username' => 'user1', 'university_username' => 'uuser1'],
            ['github_username' => 'user2', 'university_username' => 'uuser2']
        ];
        $githubToken = GithubToken::factory()->create(['token' => 'test-token']);

        Http::fake([
            'https://api.github.com/orgs/test-org/repos' => function () {
                throw new ConnectionException('Connection failed');
            },
        ]);

        $listener = new CreateRepositoryOnGit(Event::getFacadeRoot());
        $listener->handle(new RepositoriesCreationOnGithubInitiated($repositoryDetails, $members, $githubToken));
        Event::assertNotDispatched(RepositoriesCreationOnGithubCreated::class);
    }
    public function test_handle_reports_connection_exception_in_add_collaborator()
    {
        Queue::fake();
        Event::fake();

        $repositoryDetails = new CreateRepositoryDetails(
            1, 'test-org', 'test-repo', '2024-07-15'
        );
        $members = [
            ['github_username' => 'user1', 'university_username' => 'uuser1'],
            ['github_username' => 'user2', 'university_username' => 'uuser2']
        ];
        $githubToken = GithubToken::factory()->create(['token' => 'test-token']);

        Http::fake([
            'https://api.github.com/orgs/test-org/repos' => Http::response(['id' => 1, 'name' => 'test-repo'], 201),
            'https://api.github.com/repos/test-org/test-repo/collaborators/user1' => function () {
                throw new ConnectionException('Connection failed');
            },
        ]);

        $listener = new CreateRepositoryOnGit(Event::getFacadeRoot());
        $listener->handle(new RepositoriesCreationOnGithubInitiated($repositoryDetails, $members, $githubToken));
        Event::assertNotDispatched(RepositoriesCreationOnGithubCreated::class);
    }
    public function test_store_repository_and_collaborators_on_local()
    {
        Queue::fake();

        $repositoryDetails = new CreateRepositoryDetails(
            1, 'test-org', 'test-repo', '2024-07-15'
        );
        $members = [
            ['github_username' => 'user1', 'name' => 'User One', 'git_id' => 1, 'avatar_url' => 'https://example.com/avatar1.png', 'university_username' => 'uuser1'],
            ['github_username' => 'user2', 'name' => 'User Two', 'git_id' => 2, 'avatar_url' => 'https://example.com/avatar2.png', 'university_username' => 'uuser2']
        ];
        $githubToken = GithubToken::factory()->create();

        $event = new RepositoriesCreationOnGithubCreated($repositoryDetails, $members, $githubToken);

        $repositoryRepositoryMock = Mockery::mock(RepositoryRepositoryInterface::class);
        $repositoryRepositoryMock->shouldReceive('create')->andReturn(new RepositoryDto(
            id: 1,
            owner: 'test-org',
            name: 'test-repo',
            created_at: now()->toDateTimeString(),
            updated_at: now()->toDateTimeString(),
            github_token_id: $repositoryDetails->github_token_id,
            deadline: $repositoryDetails->deadline,
            token: 'token',
            collabs:new \Illuminate\Database\Eloquent\Collection(),
            commitsCount: 0,
            commitsFilesCount: 0,
            meaningfulCommitFilesCount: 0,
            NotMeaningfulCommitFilesCount: 0,
            firstCommit: null,
            lastCommit: null,
            repositoryUrl: 'https://github.com/qasm-group-project/job-searching-api',
            isCloseToDeadline: false,
            commitDashboardUrl: "dashboard.com",
            token_login_name: $githubToken->login_name,
        ));

        $userRepositoryMock = Mockery::mock(UserRepositoryInterface::class);
        $userRepositoryMock->shouldReceive('updateOrCreate')->twice();

        $listener = new StoreRepositoryAndCollaboratorsOnLocal($repositoryRepositoryMock, $userRepositoryMock);
        $listener->handle($event);

        // Verify methods were called
        $repositoryRepositoryMock->shouldHaveReceived('create')->once();
        $userRepositoryMock->shouldHaveReceived('updateOrCreate')->twice();
    }
    public function test_handle_repository_creation_failed_exception()
    {
        Queue::fake();

        $repositoryDetails = new CreateRepositoryDetails(
            1, 'test-org', 'test-repo', '2024-07-15'
        );
        $members = [
            ['github_username' => 'user1', 'name' => 'User One', 'git_id' => 1, 'avatar_url' => 'https://example.com/avatar1.png', 'university_username' => 'uuser1'],
            ['github_username' => 'user2', 'name' => 'User Two', 'git_id' => 2, 'avatar_url' => 'https://example.com/avatar2.png', 'university_username' => 'uuser2']
        ];
        $githubToken = GithubToken::factory()->create();

        $event = new RepositoriesCreationOnGithubCreated($repositoryDetails, $members, $githubToken);

        $repositoryRepositoryMock = \Mockery::mock(RepositoryRepositoryInterface::class);
        $repositoryRepositoryMock->shouldReceive('create')->andThrow(new RepositoryCreationFailedException('Repository creation failed'));

        $userRepositoryMock = \Mockery::mock(UserRepositoryInterface::class);
        $userRepositoryMock->shouldNotReceive('updateOrCreate');

        $listener = new StoreRepositoryAndCollaboratorsOnLocal($repositoryRepositoryMock, $userRepositoryMock);

        Log::shouldReceive('error')->once();

        $listener->handle($event);

        // Verify that the user creation was not attempted
        $userRepositoryMock->shouldNotHaveReceived('updateOrCreate');
    }
}

<?php

namespace Controllers;

use App\Traits\ApiResponse;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Token\database\repository\TokenRepository;
use Modules\Token\src\DTOs\CreateTokenDetails;
use Modules\Token\src\Enumerations\GithubTokenApiResponses;
use Modules\Token\src\Exceptions\TokenCreationFailedException;
use Modules\Token\src\Models\GithubToken;
use Modules\Token\tests\Controllers\TestCreateTokenDetails;
use Tests\TestCase;

class GithubTokenControllerTest extends TestCase
{
    use RefreshDatabase;
    use ApiResponse;
    public function testCreateGithubTokenController_success_scenario()
    {
        $githubToken = GithubToken::factory()->accessible()->make();
        Http::fake([
            'https://api.github.com/user' => Http::response([
                'login' => $githubToken->login_name,
                'id' => $githubToken->githubId
            ], 200)
        ]);
        $response = $this->postJson(route('token.create'), [
            'token' => 'valid-token'
        ]);
        $this->assertEquals("valid-token", $response->json()['data']['token']);
        $this->assertEquals($githubToken->login_name, $response->json()['data']['login']);
        $this->assertEquals($githubToken->githubId, $response->json()['data']['githubId']);
        $this->assertDatabaseCount('github_tokens', 1);
    }

    public function testCreateGithubTokenController_failure_scenario_empty_request_body()
    {
        $response = $this->postJson(route('token.create'));
        $this->assertEquals('The token field is required.', $response->json()['errors'][0]['message']);
        $this->assertCount(1, $response->json()['errors']);
        $response->assertStatus(422);
    }

    public function testCreateGithubTokenController_failure_scenario_repeated_token()
    {
        $githubToken = GithubToken::factory()->accessible()->create();
        $response = $this->postJson(route('token.create'), [
            'token' => $githubToken->token,
        ]);
        $this->assertEquals('The token has already been taken.', $response->json()['errors'][0]['message']);
        $this->assertCount(1, $response->json()['errors']);
        $response->assertStatus(422);
    }

    public function test_it_blocks_request_with_invalid_github_token()
    {
        Http::fake([
            'https://api.github.com/user' => Http::response([], 400)
        ]);
        $response = $this->postJson(route('token.create'), [
            'token' => 'invalid-token',
        ]);
        $this->assertEquals('Invalid token', $response->json()['message']);
    }

    public function test_it_handles_connection_exception_while_getting_user_info_base_token()
    {
        Http::fake([
            'https://api.github.com/user' => function () {
                throw new ConnectionException('Connection error');
            }
        ]);
        $response = $this->postJson(route('token.create'), [
            'token' => 'connection-error-token',
        ]);
        $this->assertEquals(GithubTokenApiResponses::ConnectionError, $response->json()['message']);
    }

    public function test_it_handles_general_exception_while_getting_user_info_base_token()
    {
        Http::fake([
            'https://api.github.com/user' => function () {
                throw new Exception('general error');
            }
        ]);
        $response = $this->postJson(route('token.create'), [
            'token' => 'general-error-token',
        ]);
        $this->assertEquals(GithubTokenApiResponses::ServerError, $response->json()['message']);
    }
    public function test_it_throws_token_creation_failed_exception_invalid_createTokenDetails()
    {
        $createTokenDetails = new TestCreateTokenDetails(
            token: null,
            login_name: 'testuser',
            githubId: 123456
        );

        // Create the TokenRepository instance
        $repository = new TokenRepository();

        // Expect the TokenCreationFailedException to be thrown
        $this->expectException(TokenCreationFailedException::class);
        $this->expectExceptionMessage('Token creation process failed!');

        // Call the create method with the test implementation
        $repository->create($createTokenDetails);
    }

    public function test_it_throws_exception_when_database_query_fails_on_create_method()
    {
        $githubToken = GithubToken::factory()->accessible()->make();
        $createTokenDetails = new CreateTokenDetails(
            token: $githubToken->token,
            login_name: $githubToken->login_name,
            githubId: $githubToken->githubId
        );

        Schema::drop('github_tokens');
        // Create the TokenRepository instance
        $repository = app(TokenRepository::class);

        // Expect an exception to be thrown
        $this->expectException(TokenCreationFailedException::class);
        // When we fetch the tokens
        $repository->create($createTokenDetails);

    }



    public function testCreateGithubTokenController_fail_scenario_internal_server_error()
    {
        $tokenRepositoryMock = Mockery::mock(TokenRepository::class);
        $tokenRepositoryMock->shouldReceive('create')
            ->andThrow(new TokenCreationFailedException('Token creation process failed!'));

        // Bind the mock to the service container
        $this->app->instance(TokenRepository::class, $tokenRepositoryMock);
        $githubToken = GithubToken::factory()->accessible()->make();
        Http::fake([
            'https://api.github.com/user' => Http::response([
                'login' => $githubToken->login_name,
                'id' => $githubToken->githubId
            ], 200)
        ]);
        $response = $this->postJson(route('token.create'), [
            'token' => 'sample-token',
        ]);
        $response->assertStatus(500);
    }
    public function testFetchGitHubToken_success_scenario()
    {
        $githubToken = GithubToken::factory()->accessible()->create();
        $response = $this->getJson(route('token.fetch'));
        $response->assertStatus(200);
        $this->assertEquals($githubToken->token, $response->json()['data'][0]['token']);
        $this->assertCount(1, $response->json()['data']);
    }
    public function test_it_returns_error_response_when_fetch_fails()
    {
        // Mock the TokenRepository to throw an exception
        $tokenRepositoryMock = Mockery::mock(TokenRepository::class);
        $tokenRepositoryMock->shouldReceive('fetch')
            ->andThrow(new Exception('Database error'));

        // Bind the mock to the service container
        $this->app->instance(TokenRepository::class, $tokenRepositoryMock);

        // Call the route
        $response = $this->getJson(route('token.fetch'));

        // Assert the response
        $response->assertStatus(500);
        $response->assertJson(['message' => 'Database error']);
    }

    public function test_it_throws_exception_when_database_query_fails_on_fetch_method()
    {

        Schema::drop('github_tokens');
        // Create the TokenRepository instance
        $repository = app(TokenRepository::class);

        // Expect an exception to be thrown
        $this->expectException(Exception::class);
        // When we fetch the tokens
        $repository->fetch();

    }
}

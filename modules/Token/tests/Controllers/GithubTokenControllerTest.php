<?php

namespace Controllers;

use App\Traits\ApiResponse;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Exceptions\RepositoryCreationFailedException;
use Modules\Repository\src\Listeners\StoreBranches;
use Modules\Repository\src\Models\Repository;
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

    // Test successful creation of GitHub token
    public function testSuccessfulGithubTokenCreation()
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

    // Test failure when request body is empty
    public function testFailureWhenRequestBodyIsEmpty()
    {
        $response = $this->postJson(route('token.create'));
        $this->assertEquals('The token field is required.', $response->json()['errors'][0]['message']);
        $this->assertCount(1, $response->json()['errors']);
        $response->assertStatus(422);
    }

    // Test failure when token is already taken
    public function testFailureWhenTokenIsAlreadyTaken()
    {
        $githubToken = GithubToken::factory()->accessible()->create();
        $response = $this->postJson(route('token.create'), [
            'token' => $githubToken->token,
        ]);
        $this->assertEquals('The token has already been taken.', $response->json()['errors'][0]['message']);
        $this->assertCount(1, $response->json()['errors']);
        $response->assertStatus(422);
    }

    // Test blocking request with invalid GitHub token
    public function testBlockingRequestWithInvalidGithubToken()
    {
        Http::fake([
            'https://api.github.com/user' => Http::response([], 400)
        ]);
        $response = $this->postJson(route('token.create'), [
            'token' => 'invalid-token',
        ]);
        $this->assertEquals('Invalid token', $response->json()['message']);
    }

    // Test handling connection exception while getting user info based on token
    public function testHandlingConnectionExceptionWhileGettingUserInfo()
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

    // Test handling general exception while getting user info based on token
    public function testHandlingGeneralExceptionWhileGettingUserInfo()
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

    // Test throwing TokenCreationFailedException with invalid CreateTokenDetails
    public function testThrowingTokenCreationFailedExceptionWithInvalidCreateTokenDetails()
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
        $this->expectExceptionMessage('Token creation failed');

        // Call the create method with the test implementation
        $repository->create($createTokenDetails);
    }

    // Test handling database query failure on create method
    public function testDatabaseQueryFailureOnCreateMethod()
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

    // Test failing scenario when internal server error occurs during token creation
    public function testInternalServerErrorDuringTokenCreation()
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

    // Test successful fetching of GitHub tokens
    public function testSuccessfulFetchingOfGithubTokens()
    {
        $githubToken = GithubToken::factory()->accessible()->create();
        $response = $this->getJson(route('token.fetch'));
        $response->assertStatus(200);
        $this->assertEquals($githubToken->token, $response->json()['data'][0]['token']);
        $this->assertCount(1, $response->json()['data']);
    }

    // Test returning error response when fetching fails
    public function testReturningErrorResponseWhenFetchFails()
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

    // Test handling database query failure on fetch method
    public function testDatabaseQueryFailureOnFetchMethod()
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

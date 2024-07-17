<?php

namespace Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Repository\src\Models\Repository;
use Modules\Token\database\repository\TokenRepository;
use Modules\Token\database\repository\TokenRepositoryInterface;
use Modules\Token\src\Exceptions\TokenDeletionFailedException;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class DeleteTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_token_successfully()
    {
        $this->withoutExceptionHandling();
        $token = GithubToken::factory()->create();

        $response = $this->postJson(route('token.delete', ['tokenId' => $token->id]));

        $response->assertStatus(200)
            ->assertJson(['success' => 'true', 'message' => "Operation successful"]);

        $this->assertDatabaseMissing('github_tokens', ['id' => $token->id]);
    }

    public function test_delete_token_not_found()
    {
        $response = $this->postJson(route('token.delete', ['tokenId' => 999]));
        $response->assertStatus(422)->assertJson(['success' => false, 'message' => "Token not found", 'errors' => []]);
    }

    public function test_delete_token_with_repositories()
    {
        $token = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'owner' => 'test-owner',
            'name' => 'test-name',
            'github_token_id' => $token->id,
            'deadline' => now()->addDays(1),
        ]);
        $response = $this->postJson(route('token.delete', ['tokenId' => $token->id]));
        $response->assertStatus(400)->assertJson([
            'success' => false,
            'message' => "Token has repositories added to it. Please remove them first",
            'errors' => []
        ]);
    }

    public function test_delete_token_throws_token_deletion_failed_exception()
    {
        $this->expectException(TokenDeletionFailedException::class);
        Schema::drop('github_tokens');
        $repository = new TokenRepository();
        $repository->delete(999);
    }

    public function test_delete_token_throws_token_deletion_failed_exception_TokenDeletionFailedException()
    {
        $token = GithubToken::factory()->create();

        // Mock the repository to throw TokenDeletionFailedException
        $repositoryMock = Mockery::mock(TokenRepositoryInterface::class);
        $repositoryMock->shouldReceive('delete')
            ->with($token->id)
            ->andThrow(new TokenDeletionFailedException('Token deletion failed!'));

        // Replace the repository in the container with the mock
        $this->app->instance(TokenRepositoryInterface::class, $repositoryMock);

        // Ensure that an error is logged
        Log::shouldReceive('error')->once();

        // Call the route
        $response = $this->postJson(route('token.delete', ['tokenId' => $token->id]));

        // Assert the response
        $response->assertStatus(400);
    }
}

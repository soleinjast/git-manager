<?php

namespace Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Modules\User\database\repository\UserRepositoryInterface;
use Modules\User\src\DTOs\UserCreateDetails;
use Modules\User\src\Middleware\CheckUserExistence;
use Modules\User\src\Middleware\PrepareRequestForUpdatingUser;
use Modules\User\src\Models\User;
use Tests\TestCase;

class UpdateUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_valid_request()
    {
        $request = Request::create('/update', 'P', [
            'git_id' => '123',
            'repository_id' => '456',
            'login_name' => 'test_login',
            'name' => 'Test Name',
            'avatar_url' => 'http://example.com/avatar.png',
            'university_username' => 'uni_test',
            'status' => 'active',
        ]);

        $middleware = new PrepareRequestForUpdatingUser();

        $response = $middleware->handle($request, function ($request) {
            return response('Next middleware');
        });
        $this->assertEquals('Next middleware', $response->getContent());
    }
    public function test_middleware_invalid_request()
    {
        $request = Request::create('/update', 'POST', [
            'git_id' => '123',
            'repository_id' => '',
            'login_name' => 'test_login',
            'name' => 'Test Name',
            'avatar_url' => 'https://example.com/avatar.png',
            'university_username' => 'uni_test',
            'status' => 'active',
        ]);

        $middleware = new PrepareRequestForUpdatingUser();

        $response = $middleware->handle($request, function ($request) {
            return response('Next middleware');
        });

        $this->assertEquals(422, $response->status());
    }
    public function test_user_exists()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $user = User::factory()->create([
            'git_id' => '123',
            'repository_id' => '1'
        ]);

        $request = Request::create('/update', 'POST', [
            'git_id' => '123',
            'repository_id' => '1',
        ]);

        $middleware = new CheckUserExistence();

        $response = $middleware->handle($request, function ($request) {
            return response('Next middleware');
        });

        $this->assertEquals('Next middleware', $response->getContent());
    }
    public function test_user_does_not_exist()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $request = Request::create('/update', 'POST', [
            'git_id' => '123',
            'repository_id' => '1',
        ]);

        $middleware = new CheckUserExistence();

        $response = $middleware->handle($request, function ($request) {
            return response('Next middleware');
        });

        $this->assertEquals(400, $response->status());
    }
    public function test_update_user_success()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $user = User::factory()->create([
            'git_id' => '123',
            'repository_id' => '1'
        ]);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('updateOrCreate')
            ->once()
            ->with(Mockery::type(UserCreateDetails::class));
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $response = $this->postJson(route('user.update'), [
            'git_id' => '123',
            'repository_id' => '1',
            'login_name' => 'test_login',
            'name' => 'Test Name',
            'avatar_url' => 'https://example.com/avatar.png',
            'university_username' => 'uni_test',
            'status' => 'approved',
        ]);
        $response->assertStatus(200);
    }

    public function test_update_user_failure()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $user = User::factory()->create([
            'git_id' => '123',
            'repository_id' => '1'
        ]);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('updateOrCreate')
            ->once()
            ->with(Mockery::type(UserCreateDetails::class))
            ->andThrow(new \Exception('Error'));

        $this->app->instance(UserRepositoryInterface::class, $userRepository);

        $response = $this->postJson(route('user.update'), [
            'git_id' => '123',
            'repository_id' => '1',
            'login_name' => 'test_login',
            'name' => 'Test Name',
            'avatar_url' => 'https://example.com/avatar.png',
            'university_username' => 'uni_test',
            'status' => 'approved',
        ]);
        $response->assertStatus(500);
    }
    public function test_exception_handling()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $user = User::factory()->create([
            'git_id' => '123',
            'repository_id' => '1'
        ]);
        // Create a request
        $request = Request::create('/update', 'POST', [
            'git_id' => '123',
            'repository_id' => '1',
        ]);

        // Instantiate the middleware
        $middleware = new CheckUserExistence();
        Schema::drop('users');
        // Invoke the middleware handle method
        $response = $middleware->handle($request, function ($request) {
            return response('Next middleware');
        });

        // Assert that the response status is 500
        $this->assertEquals(500, $response->status());
    }
}

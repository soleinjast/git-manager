<?php

namespace Modules\Commit\tests\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\Commit\src\Models\Commit;
use Modules\Repository\database\repository\RepositoryRepository;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Repository\src\Exceptions\RetrieveRepositoryWithCommitsFailedException;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class FetchRepositoryCommitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testValidRepositoryIdPassesMiddleware()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();

        $response = $this->getJson(route('commit.fetch', ['repoId' => $repository->id]));
        $this->assertCount(0, $response->json()['data']['commits']);
        $response->assertStatus(200);
    }

    public function testInvalidRepositoryIdFailsMiddleware()
    {
        $response = $this->getJson(route('commit.fetch', ['repoId' => 999]));
        $response->assertStatus(400);
        $this->assertEquals(RepositoryResponseEnums::REPOSITORY_NOT_FOUND, $response->json()['message']);
    }
    public function testFetchRepositoryCommits()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'github_token_id' => $githubToken->id,
        ]);

        Commit::factory()->count(15)->create([
            'repository_id' => $repository->id,
        ]);

        $response = $this->getJson(route('commit.fetch', ['repoId' => $repository->id]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'repository' => [
                    'id',
                    'owner',
                    'name',
                    'created_at',
                    'updated_at',
                    'github_token_id',
                    'deadline',
                    'token'
                ],
                'commits',
                'current_page',
                'last_page',
                'next_page_url',
                'prev_page_url'
            ]
        ]);
    }
    public function testFetchRepositoryCommitsWithFilters()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'github_token_id' => $githubToken->id,
        ]);

        $author = '55';
        Commit::factory()->count(10)->create([
            'repository_id' => $repository->id,
            'author_git_id' => $author,
        ]);

        $response = $this->getJson(route('commit.fetch', ['repoId' => $repository->id]) . '?author=' . $author);
        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data.commits');
    }

    public function testFetchRepositoryCommitsInvalidRepository()
    {
        $response = $this->getJson(route('commit.fetch', ['repoId' => 999]));
        $response->assertStatus(400);
        $response->assertJson(['message' => 'Repository not found!', 'success' => false, 'errors' => []]);
    }

    public function testFetchRepositoryCommitsHandlesException()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create([
            'github_token_id' => $githubToken->id,
        ]);
        $repositoryRepositoryMock = Mockery::mock(RepositoryRepositoryInterface::class);
        $repositoryRepositoryMock->shouldReceive('getRepositoryWithCommits')
            ->andThrow(new RetrieveRepositoryWithCommitsFailedException('Failed to retrieve commits'));

        $this->app->instance(RepositoryRepositoryInterface::class, $repositoryRepositoryMock);

        $response = $this->getJson(route('commit.fetch', ['repoId' => 1]));

        $response->assertStatus(400);
    }
    public function testGetRepositoryWithCommitsHandlesGeneralException()
    {
        $repositoryId = 1;
        $perPage = 10;
        $author = 'test-author';
        $startDate = '2022-01-01';
        $endDate = '2022-12-31';

        $repositoryMock = Mockery::mock(Repository::class);
        $repositoryMock->shouldReceive('findOrFail')
            ->with($repositoryId)
            ->andThrow(new \Exception('General exception'));

        $repositoryRepo = Mockery::mock(RepositoryRepository::class)->makePartial();
        $repositoryRepo->shouldReceive('getRepositoryWithCommits')
            ->passthru();
        $repositoryRepo->shouldReceive('query')
            ->andReturn($repositoryMock);

        $this->app->instance(RepositoryRepository::class, $repositoryRepo);

        $this->expectException(RetrieveRepositoryWithCommitsFailedException::class);
        $this->expectExceptionMessage('Failed to retrieve repository with commits.');

        $repositoryRepo->getRepositoryWithCommits(
            $repositoryId,
            $perPage,
            $author,
            $startDate,
            $endDate
        );
    }
}

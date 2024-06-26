<?php

namespace Modules\Commit\tests\Actions;

use App\Services\GithubService;
use App\Services\GitService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Commit\database\repository\CommitFileRepository;
use Modules\Commit\database\repository\CommitFileRepositoryInterface;
use Modules\Commit\src\DTOs\CommitDto;
use Modules\Commit\src\DTOs\CreateCommitFileDetails;
use Modules\Commit\src\Events\CommitCreated;
use Modules\Commit\src\Jobs\ProcessCommitFile;
use Modules\Commit\src\Listeners\StoreCommitFiles;
use Modules\Commit\src\Models\Commit;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Models\Repository;
use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class CreateCommitFileTest extends TestCase
{
    use RefreshDatabase;
    public function test_update_or_create_method()
    {
        $githubToken = GithubToken::factory()->create();
        $repository = Repository::factory()->create();
        $commit = Commit::factory()->create([
            'repository_id' => $repository->id
        ]);
        $repository = new CommitFileRepository();

        $commitFileDetails = new CreateCommitFileDetails(
            commit_id: 1,
            filename: 'test_file.php',
            status: 'added',
            changes: 10,
            meaningful: true
        );

        // Call the method
        $repository->updateOrCreate($commitFileDetails);

        // Assert that the commit file was created
        $this->assertDatabaseHas('commit_files', [
            'commit_id' => 1,
            'filename' => 'test_file.php',
            'status' => 'added',
            'changes' => 10,
            'meaningful' => true,
        ]);

        // Call the method again with the same details but different changes
        $commitFileDetails = new CreateCommitFileDetails(
            commit_id: 1,
            filename: 'test_file.php',
            status: 'modified',
            changes: 20,
            meaningful: true
        );

        $repository->updateOrCreate($commitFileDetails);

        // Assert that the commit file was updated
        $this->assertDatabaseHas('commit_files', [
            'commit_id' => 1,
            'filename' => 'test_file.php',
            'status' => 'modified',
            'changes' => 20,
            'meaningful' => true,
        ]);
    }
    public function test_update_or_create_method_catches_exception_and_logs_error()
    {
        $repository = new CommitFileRepository();

        $commitFileDetails = new CreateCommitFileDetails(
            commit_id: 1,
            filename: 'test_file.php',
            status: 'added',
            changes: 10,
            meaningful: true
        );

        // Drop the commit_files table to simulate a missing table
        Schema::drop('commit_files');

        // Spy on the Log facade to check if the exception is logged
        Log::spy();

        // Call the method and expect it to handle the exception
        $repository->updateOrCreate($commitFileDetails);

        // Assert that the exception was logged
        Log::shouldHaveReceived('error')
            ->withArgs(function ($message) {
                return str_contains($message, 'SQLSTATE[HY000]');
            })
            ->once();
    }

    public function test_handle_method_dispatches_process_commit_file_jobs()
    {
        Queue::fake();
        $githubToken = GithubToken::factory()->create();
        // Create a repository and commit
        $repository = Repository::factory()->create();
        $commitDto = new CommitDto(
            id: 1,
            repositoryId: $repository->id,
            sha: '123456',
            message: 'Initial commit',
            author: 'John Doe',
            date: '2024-06-22T12:00:00Z',is_first_commit: false
        );

        // Fake the GitHub API response
        Http::fake([
            'https://api.github.com/repos/' . $repository->owner . '/' . $repository->name . '/commits/123456' => Http::response([
                'files' => [
                    [
                        'filename' => 'test_file.php',
                        'status' => 'added',
                        'patch' => 'diff --git a/test_file.php b/test_file.php\nnew file mode 100644\nindex 0000000..e69de29'
                    ],
                    [
                        'filename' => 'another_file.php',
                        'status' => 'modified',
                        'patch' => 'diff --git a/another_file.php b/another_file.php\nindex e69de29..d41d8cd'
                    ],
                ],
            ], 200),
        ]);

        $repositoryDto = RepositoryDto::fromEloquent($repository);

        // Create the event and listener
        $event = new CommitCreated($repositoryDto, $commitDto);
        $listener = new StoreCommitFiles();

        // Handle the event
        $listener->handle($event);

        // Assert that the ProcessCommitFile jobs were dispatched
        Queue::assertPushed(ProcessCommitFile::class, function ($job) {
            return $job->fileData['filename'] === 'test_file.php' &&
                $job->fileData['patch'] === 'diff --git a/test_file.php b/test_file.php\nnew file mode 100644\nindex 0000000..e69de29';
        });

        Queue::assertPushed(ProcessCommitFile::class, function ($job) {
            return $job->fileData['filename'] === 'another_file.php' &&
                $job->fileData['patch'] === 'diff --git a/another_file.php b/another_file.php\nindex e69de29..d41d8cd';
        });

        Queue::assertPushed(ProcessCommitFile::class, 2);
    }

    public function test_handle_method_calls_update_or_create_on_commit_file_repository()
    {
        $fileData = [
            'filename' => 'test_file.php',
            'status' => 'modified',
            'patch' => 'diff --git a/test_file.php b/test_file.php\nindex 0000000..e69de29'
        ];

        $commitId = 1;
        $isFirstCommit = false;

        // Mock the CommitFileRepositoryInterface
        $commitFileRepositoryMock = Mockery::mock(CommitFileRepositoryInterface::class);
        $commitFileRepositoryMock->shouldReceive('updateOrCreate')
            ->once()
            ->with(Mockery::on(function (CreateCommitFileDetails $details) use ($commitId, $fileData, $isFirstCommit) {
                return $details->commit_id === $commitId &&
                    $details->filename === $fileData['filename'] &&
                    $details->status === $fileData['status'] &&
                    $details->changes === $fileData['patch'] &&
                    $details->meaningful === false;
            }));

        // Mock the GithubService
        $githubServiceMock = Mockery::mock(GithubService::class);
        $githubServiceMock->shouldReceive('isMeaningfulPatch')
            ->once()
            ->with($fileData['patch'])
            ->andReturn(false);

        // Create an instance of the job
        $job = new ProcessCommitFile($fileData, $commitId, $isFirstCommit);

        // Call the handle method
        $job->handle($commitFileRepositoryMock, $githubServiceMock);
    }

    public function test_handle_method_marks_file_as_meaningful_if_patch_is_meaningful()
    {
        $fileData = [
            'filename' => 'test_file.php',
            'status' => 'modified',
            'patch' => 'diff --git a/test_file.php b/test_file.php\nindex 0000000..e69de29'
        ];

        $commitId = 1;
        $isFirstCommit = false;

        // Mock the CommitFileRepositoryInterface
        $commitFileRepositoryMock = Mockery::mock(CommitFileRepositoryInterface::class);
        $commitFileRepositoryMock->shouldReceive('updateOrCreate')
            ->once()
            ->with(Mockery::on(function (CreateCommitFileDetails $details) use ($commitId, $fileData, $isFirstCommit) {
                return $details->commit_id === $commitId &&
                    $details->filename === $fileData['filename'] &&
                    $details->status === $fileData['status'] &&
                    $details->changes === $fileData['patch'] &&
                    $details->meaningful === true;
            }));

        // Mock the GithubService
        $githubServiceMock = Mockery::mock(GithubService::class);
        $githubServiceMock->shouldReceive('isMeaningfulPatch')
            ->once()
            ->with($fileData['patch'])
            ->andReturn(true);

        // Create an instance of the job
        $job = new ProcessCommitFile($fileData, $commitId, $isFirstCommit);

        // Call the handle method
        $job->handle($commitFileRepositoryMock, $githubServiceMock);
    }

    public function test_fetch_commit_files_success()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-repo/commits/test-sha' => Http::response([
                'files' => [
                    [
                        'filename' => 'file1.php',
                        'status' => 'modified',
                        'patch' => 'diff --git a/file1.php b/file1.php',
                    ],
                    [
                        'filename' => 'file2.php',
                        'status' => 'added',
                        'patch' => 'diff --git a/file2.php b/file2.php',
                    ],
                ]
            ], 200)
        ]);

        $githubService = new GithubService();
        $githubService->setModel('test-token', 'test-owner', 'test-repo');

        $files = $githubService->fetchCommitFiles('test-sha');

        $this->assertCount(2, $files);
        $this->assertEquals('file1.php', $files[0]['filename']);
        $this->assertEquals('file2.php', $files[1]['filename']);
    }

    public function test_fetch_commit_files_connection_exception()
    {
        Http::fake(function (Request $request) {
            throw new \Illuminate\Http\Client\ConnectionException();
        });

        $githubService = new GithubService();
        $githubService->setModel('test-token', 'test-owner', 'test-repo');

        $files = $githubService->fetchCommitFiles('test-sha');

        $this->assertEmpty($files);
    }

    public function test_fetch_commit_files_unsuccessful_response()
    {
        Http::fake([
            'https://api.github.com/repos/test-owner/test-repo/commits/test-sha' => Http::response(null, 500)
        ]);

        $githubService = new GithubService();
        $githubService->setModel('test-token', 'test-owner', 'test-repo');

        $files = $githubService->fetchCommitFiles('test-sha');

        $this->assertEmpty($files);
    }

    public function test_is_meaningful_patch_with_meaningful_changes()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,4 @@
 Line 1
 Line 2
+Line 3
-Line 4
EOD;

        $this->assertTrue($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_whitespace_changes()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,3 @@
 Line 1
 Line 2
-Line 3
+Line 3
EOD;

        $this->assertFalse($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_no_changes()
    {
        $gitService = new GitService();
        $patch = "";

        $this->assertFalse($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_only_added_lines()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,4 @@
 Line 1
 Line 2
+Line 3
EOD;

        $this->assertTrue($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_only_removed_lines()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,4 +1,3 @@
 Line 1
 Line 2
 Line 3
-Line 4
EOD;

        $this->assertTrue($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_identical_add_and_remove_lines()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,3 @@
 Line 1
 Line 2
-Line 3
+Line 3
EOD;

        $this->assertFalse($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_whitespace_changes_only()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,3 @@
 Line 1
 Line 2
-Line 3
+ Line 3
EOD;

        $this->assertFalse($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_whitespace_changes_in_addition()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,3 @@
 Line 1
 Line 2
-Line 3
+Line 3
EOD;

        $this->assertFalse($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_single_line_space_addition()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,4 @@
 Line 1
 Line 2
-Line 3
+
+Line 3
EOD;

        $this->assertFalse($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_whitespace_changes_between_words()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,3 @@
 Line 1
 Line 2
-Line 3 with words
+Line 3  with  words
EOD;

        $this->assertFalse($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_break_line_space()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,3 @@
 Line 1
 Line 2
-Line 3 with words
+Line 3 with words
EOD;

        $this->assertFalse($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_whitespace_changes_between_word_characters()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,3 @@
 Line 1
 Line 2
-L i ne 3 w ith words
+Line 3  with  words
EOD;

        $this->assertFalse($gitService->isMeaningfulPatch($patch));
    }

    public function test_is_meaningful_patch_with_break_lines()
    {
        $gitService = new GitService();
        $patch = <<<EOD
diff --git a/file.txt b/file.txt
index 83db48f..f7359e6 100644
@@ -1,3 +1,3 @@
 Line 1
 Line 2
-Line 3 with words
+Line
+3
+with
+words
EOD;
        $this->assertFalse($gitService->isMeaningfulPatch($patch));
    }

}

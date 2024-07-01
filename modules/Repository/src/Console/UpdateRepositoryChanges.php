<?php

namespace Modules\Repository\src\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Modules\Commit\src\Exceptions\ChunkAllRepositoriesFailedException;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Events\RepositoryUpdate;
use Modules\Repository\src\Exceptions\RepositoryRetrievalFailedException;

class UpdateRepositoryChanges extends Command
{
    public function __construct(protected Dispatcher $events,  protected RepositoryRepositoryInterface $repositoryRepository)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-repository-changes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update repository changes';


    /**
     * @throws ChunkAllRepositoriesFailedException
     */
    public function handle(): void
    {
        $chunkSize = 10;

        try {
            $this->repositoryRepository->chunkAll($chunkSize, function ($repositories) {
                foreach ($repositories as $repository) {
                    $this->events->dispatch(new RepositoryUpdate(RepositoryDto::fromEloquent($repository)));
                }
            });
        } catch (\Exception $e) {
            report($e);
            throw new ChunkAllRepositoriesFailedException($e->getMessage());
        }
    }
}

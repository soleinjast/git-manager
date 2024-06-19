<?php

namespace Modules\Repository\tests\Models;

use Modules\Repository\src\Models\Repository;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    public function testFactory()
    {
        $repository = Repository::factory()->make();
        $this->assertIsString($repository->owner);
        $this->assertIsString($repository->name);
    }
    public function testFillable()
    {
        $repository = new Repository();
        $this->assertEquals(['owner', 'name', 'github_token_id', 'deadline'], $repository->getFillable());
    }
    public function testCasts()
    {
        $repository = new Repository();
        $this->assertEquals(['created_at' => 'datetime:Y-m-d H-i-s', 'updated_at' => 'datetime:Y-m-d H-i-s', 'deadline' => 'datetime:Y-m-d H-i-s' ,'id' => 'int'], $repository->getCasts());
    }
}

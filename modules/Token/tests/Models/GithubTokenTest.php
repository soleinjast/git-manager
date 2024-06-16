<?php

namespace Modules\Token\tests\Models;

use Modules\Token\src\Models\GithubToken;
use Tests\TestCase;

class GithubTokenTest extends TestCase
{
    public function testFactory()
    {
        $token = GithubToken::factory()->make();
        $this->assertIsString($token->token);
        $this->assertIsString($token->login_name);
        $this->assertIsInt($token->githubId);
    }
    public function testFillable()
    {
        $token = new GithubToken();
        $this->assertEquals(['token', 'login_name', 'githubId'], $token->getFillable());
    }

    public function testCasts()
    {
        $token = new GithubToken();
        $this->assertEquals(['created_at' => 'datetime:Y-m-d H-i-s', 'updated_at' => 'datetime:Y-m-d H-i-s', 'id' => 'int'], $token->getCasts());
    }
}

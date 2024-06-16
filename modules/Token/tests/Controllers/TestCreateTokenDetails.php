<?php

namespace Modules\Token\tests\Controllers;

use Modules\Token\src\DTOs\CreateTokenDetailsInterface;

class TestCreateTokenDetails implements CreateTokenDetailsInterface
{
    public function __construct(public ?string $token,public string $login_name, public string $githubId)
    {

    }
}

<?php

namespace Modules\Token\src\DTOs;

class CreateTokenDetails implements CreateTokenDetailsInterface
{
    public function __construct(public string $token, public string $login_name, public string $githubId)
    {

    }
}

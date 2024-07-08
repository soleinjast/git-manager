<?php

namespace Modules\User\src\DTOs;

class UserCreateDetails
{
    public function __construct(public int $repositoryId,
                                public string $login_name,
                                public string $name,
                                public string $git_id,
                                public string $avatar_url,
                                public string $university_username,
                                public string $status,
    )
    {

    }
}

<?php

namespace Modules\Repository\src\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMemberFields implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($value as $member) {
            if (empty($member['github_username'])) {
                $fail("A github username missed! Each member must have a github username.");
            }
        }
    }
}

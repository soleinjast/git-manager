<?php

namespace Modules\Repository\src\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueArrayValues implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $githubUsernames = array_column($value, 'github_username');
        $universityUsernames = array_filter(array_column($value, 'university_username')); // Filter out null values

        if (count($githubUsernames) !== count(array_unique($githubUsernames))) {
            $fail("The {$attribute} field has duplicate GitHub usernames.");
        }

        if (count($universityUsernames) !== count(array_unique($universityUsernames))) {
            $fail("The {$attribute} field has duplicate university usernames.");
        }
    }
}

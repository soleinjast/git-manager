<?php

namespace Modules\Repository\src\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SufficientMembers implements ValidationRule
{
    private int $groupCount;
    private int $membersPerGroup;

    public function __construct(int $groupCount, int $membersPerGroup)
    {
        $this->groupCount = $groupCount;
        $this->membersPerGroup = $membersPerGroup;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (count($value) < $this->groupCount * $this->membersPerGroup) {
            $fail('The number of members is not sufficient for the specified number of groups and members per group.');
        }
    }
}

<?php

namespace App\Domain\CustomerAccess;

use App\Models\CustomerAccessGrant;

final readonly class IssuedGrant
{
    public function __construct(public CustomerAccessGrant $grant, #[\SensitiveParameter] public string $credential) {}
}

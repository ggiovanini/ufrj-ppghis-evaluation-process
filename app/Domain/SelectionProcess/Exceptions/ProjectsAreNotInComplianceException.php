<?php

namespace App\Domain\SelectionProcess\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class ProjectsAreNotInComplianceException extends DomainException
{
    public function statusCode(): int
    {
        return 422;
    }
}

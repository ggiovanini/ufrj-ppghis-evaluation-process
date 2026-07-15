<?php

namespace App\Domain\Projects\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class ValidateImportException extends DomainException
{
    public function statusCode(): int
    {
        return 422;
    }
}

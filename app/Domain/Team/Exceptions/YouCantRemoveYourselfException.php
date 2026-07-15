<?php

namespace App\Domain\Team\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class YouCantRemoveYourselfException extends DomainException
{
    public function statusCode(): int
    {
        return 422;
    }
}

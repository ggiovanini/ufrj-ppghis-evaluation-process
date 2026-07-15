<?php

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    abstract public function statusCode(): int;

    public function errorCode(): string
    {
        return class_basename($this);
    }

    public function payload(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [
            'code' => $this->errorCode(),
            'message' => $this->getMessage(),
            ...$this->payload(),
        ];
    }
}

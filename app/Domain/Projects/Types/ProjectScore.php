<?php

namespace App\Domain\Projects\Types;

use Illuminate\Support\Str;

class ProjectScore
{
    protected int $value;

    public function __construct(string $value)
    {
        $this->value = (int) Str::limit(Str::replace('.', '', $value.'000'), 3, '');
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function format(): string
    {
        $value = (string) $this->value;
        $value = $value.'000';

        return "$value[0],$value[1]$value[2]";
    }

    public static function make(?int $value): self
    {
        return new self((string) $value ?? 0);
    }
}

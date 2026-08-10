<?php

namespace App\Domain\Projects\Types;

class ProjectScore
{
    protected int $value;

    public function __construct(string $value)
    {
        $normalizedValue = str_replace(',', '.', trim($value));
        $this->value = (int) round((float) $normalizedValue * 100);
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
        $integerPart = intdiv($this->value, 100);
        $decimalPart = str_pad((string) ($this->value % 100), 2, '0', STR_PAD_LEFT);

        return "$integerPart,$decimalPart";
    }

    public static function make(?int $value): self
    {
        return new self(number_format(($value ?? 0) / 100, 2, '.', ''));
    }
}

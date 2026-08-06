<?php

namespace App\Domain\Projects\Types;

enum ProjectModality: string
{
    case MASTER = 'master';
    case DOCTORATE = 'doctorate';

    public function label(): string
    {
        return match ($this) {
            self::MASTER => 'Mestrado',
            self::DOCTORATE => 'Doutorado',
        };
    }
}

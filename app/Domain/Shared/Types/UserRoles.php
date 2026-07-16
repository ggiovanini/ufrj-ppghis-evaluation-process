<?php

namespace App\Domain\Shared\Types;

enum UserRoles: string
{
    case ADMIN = 'admin';
    case REVIEWER = 'reviewer';
    case MASTER_COMMITTEE = 'master_committee';
    case DOCTORATE_COMMITTEE = 'doctorate_committee';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::REVIEWER => 'Avaliador',
            self::MASTER_COMMITTEE => 'Banca de Mestrado',
            self::DOCTORATE_COMMITTEE => 'Banca de Doutorado',
        };
    }
}

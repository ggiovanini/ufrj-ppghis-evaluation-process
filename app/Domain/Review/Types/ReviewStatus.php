<?php

namespace App\Domain\Review\Types;

enum ReviewStatus: string
{
    case PENDENT = 'pendent';
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::PENDENT => 'Pendente',
            self::DRAFT => 'Rascunho',
            self::SUBMITTED => 'Enviado',
        };
    }
}

<?php

namespace App\Domain\Review\Types;

enum ReviewScore: int
{
    case APPROVED = 10;
    case APPROVED_WITH_RESERVATIONS = 8;
    case INDICATION_TO_DISAPPROVAL = 5;
    case DISAPPROVED = 3;
    case PENDENT = 0;

    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Aprovado',
            self::APPROVED_WITH_RESERVATIONS => 'Aprovado com ressalvas',
            self::INDICATION_TO_DISAPPROVAL => 'Indicação para reprovação',
            self::DISAPPROVED => 'Reprovado',
            self::PENDENT => 'Não avaliado',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::APPROVED => 'Ótimo nível',
            self::APPROVED_WITH_RESERVATIONS => 'Merece a aprovação, mas apresenta falhas',
            self::INDICATION_TO_DISAPPROVAL => 'Apresenta qualidades, mas não atende aos requisitos mínimos',
            self::DISAPPROVED => 'Inadequado ou muito insatisfatório',
            self::PENDENT => 'Aguardando avaliação',
        };
    }
}

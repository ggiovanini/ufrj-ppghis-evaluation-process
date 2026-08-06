<?php

namespace App\Domain\SelectionProcess\Types;

enum SelectionProcessPhases: string
{
    case IMPORT = 'IMPORT';
    case HOMOLOGATION = 'HOMOLOGATION';
    case DISTRIBUTION = 'DISTRIBUTION';
    case REVIEW = 'REVIEW';
    case WRITTEN_EXAM = 'WRITTEN_EXAM';
    case COMMITTEE = 'COMMITTEE';
    case RESULTS = 'RESULTS';
    case FINISHED = 'FINISHED';

    public function label(): string
    {
        return match ($this) {
            self::IMPORT => 'Importação',
            self::HOMOLOGATION => 'Homologação',
            self::DISTRIBUTION => 'Distribuição',
            self::REVIEW => 'Revisão',
            self::WRITTEN_EXAM => 'Prova escrita',
            self::COMMITTEE => 'Comitê',
            self::RESULTS => 'Resultados',
            self::FINISHED => 'Finalizado',
        };
    }
}

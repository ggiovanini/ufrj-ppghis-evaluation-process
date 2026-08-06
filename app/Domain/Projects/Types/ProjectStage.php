<?php

namespace App\Domain\Projects\Types;

enum ProjectStage: string
{
    case IMPORTED = 'imported';
    case HOMOLOGATED = 'homologated';

    case REVIEW = 'review';
    case WRITTEN_EXAM = 'written_exam';
    case COMMITTEE = 'committee';
    case FINISHED = 'finished';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::IMPORTED => 'Em homologação',
            self::HOMOLOGATED => 'Em distribuição',
            self::REVIEW => 'Em avaliação',
            self::WRITTEN_EXAM => 'Em aplicação de prova',
            self::COMMITTEE => 'Em avaliação do comitê',
            self::FINISHED => 'Aprovado',
            self::REJECTED => 'Reprovado',
        };
    }
}

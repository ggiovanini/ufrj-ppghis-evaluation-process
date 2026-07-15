<?php

namespace App\Domain\SelectionProcess\Types;

enum SelectionProcessPhases: string
{
    case IMPORT = 'IMPORT';
    case REVIEW = 'REVIEW';
    case WRITTEN_EXAM = 'WRITTEN_EXAM';
    case COMMITTEE = 'COMMITTEE';
    case RESULTS = 'RESULTS';
    case FINISHED = 'FINISHED';
}

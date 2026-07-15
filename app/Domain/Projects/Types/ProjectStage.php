<?php

namespace App\Domain\Projects\Types;

enum ProjectStage: string
{
    case IMPORTED = 'imported';
    case REVIEW = 'review';
    case WRITTEN_EXAM = 'written_exam';
    case COMMITTEE = 'committee';
    case FINISHED = 'finished';
    case REJECTED = 'rejected';
}

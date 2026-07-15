<?php

namespace App\Domain\Review\Types;

enum ReviewStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
}

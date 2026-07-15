<?php

namespace App\Domain\Committee\Types;

enum FinalStatus: string
{
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}

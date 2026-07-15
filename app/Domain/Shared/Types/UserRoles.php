<?php

namespace App\Domain\Shared\Types;

enum UserRoles: string
{
    case ADMIN = 'admin';
    case REVIEWER = 'reviewer';
    case MASTER_COMMITTEE = 'master_committee';
    case DOCTORATE_COMMITTEE = 'doctorate_committee';
}

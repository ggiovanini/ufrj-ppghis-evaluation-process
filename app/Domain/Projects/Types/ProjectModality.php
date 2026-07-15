<?php

namespace App\Domain\Projects\Types;

enum ProjectModality: string
{
    case MASTER = 'master';
    case DOCTORATE = 'doctorate';
}

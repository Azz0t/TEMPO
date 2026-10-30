<?php

namespace App\Enum;

enum SprintStatus: string
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
}
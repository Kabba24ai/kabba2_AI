<?php

namespace App\Modules\SchedulingAssistant\Enums;

enum AssignmentRelationshipType: string
{
    case PRIMARY = 'primary';
    case UPGRADE = 'upgrade';
    case DOWNGRADE = 'downgrade';
    case UNKNOWN = 'unknown';
}

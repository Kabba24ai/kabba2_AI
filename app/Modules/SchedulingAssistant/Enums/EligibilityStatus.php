<?php

namespace App\Modules\SchedulingAssistant\Enums;

enum EligibilityStatus: string
{
    case READY = 'ready';
    case CONDITIONAL = 'conditional';
    case APPROVAL_REQUIRED = 'approval_required';
    case BLOCKED = 'blocked';
}

<?php

namespace App\Enums\FieldService;

enum FieldTicketEventType: string
{
    case Created             = 'created';
    case StatusChanged       = 'status_changed';
    case OperationalDecision = 'operational_decision';
    case DispatchUpdated     = 'dispatch_updated';
    case MediaUpdated        = 'media_updated';
    case NoteAdded           = 'note_added';

    public function label(): string
    {
        return match ($this) {
            self::Created             => 'Ticket Created',
            self::StatusChanged       => 'Mission Status Changed',
            self::OperationalDecision => 'Operational Decision',
            self::DispatchUpdated     => 'Dispatch Assignment Updated',
            self::MediaUpdated        => 'Media Checklist Updated',
            self::NoteAdded           => 'Note Added',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created             => 'bg-green-400',
            self::StatusChanged       => 'bg-blue-400',
            self::OperationalDecision => 'bg-purple-500',
            self::DispatchUpdated     => 'bg-indigo-400',
            self::MediaUpdated        => 'bg-amber-400',
            self::NoteAdded           => 'bg-slate-400',
        };
    }
}

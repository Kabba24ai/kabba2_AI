<?php

namespace App\Models\Service;

use App\Enums\Service\ServiceTicketEventType;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * General ticket documentation entries (workbench left rail). Diagnostic
 * findings do NOT belong here — those are structured diagnostic steps.
 */
class ServiceTicketNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_ticket_id',
        'note',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::created(function (self $note) {
            ServiceTicketEvent::record(
                $note->service_ticket_id,
                ServiceTicketEventType::NoteAdded,
                notes: \Illuminate\Support\Str::limit($note->note, 120),
            );
        });

        static::updated(function (self $note) {
            if ($note->wasChanged('note')) {
                ServiceTicketEvent::record(
                    $note->service_ticket_id,
                    ServiceTicketEventType::NoteUpdated,
                    notes: \Illuminate\Support\Str::limit($note->note, 120),
                );
            }
        });
    }

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

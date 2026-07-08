<?php

namespace App\Models\FieldService;

use App\Enums\FieldService\FieldTicketEventType;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only timeline entry for a field service ticket — recorded by
 * model hooks and the mission-status flow, never edited or deleted.
 */
class FieldServiceTicketEvent extends Model
{
    protected $fillable = [
        'field_service_ticket_id',
        'event_type',
        'old_value',
        'new_value',
        'user_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'event_type' => FieldTicketEventType::class,
        'metadata'   => 'array',
    ];

    /** Single write path for timeline entries; user defaults to whoever is signed in. */
    public static function record(
        int $ticketId,
        FieldTicketEventType $type,
        ?string $old = null,
        ?string $new = null,
        ?string $notes = null,
        ?array $metadata = null,
    ): self {
        return self::create([
            'field_service_ticket_id' => $ticketId,
            'event_type'              => $type,
            'old_value'               => $old,
            'new_value'               => $new,
            'user_id'                 => auth()->id(),
            'notes'                   => $notes,
            'metadata'                => $metadata,
        ]);
    }

    public function ticket()
    {
        return $this->belongsTo(FieldServiceTicket::class, 'field_service_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

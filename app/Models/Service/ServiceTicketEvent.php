<?php

namespace App\Models\Service;

use App\Enums\Service\ServiceTicketEventType;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only timeline entry for a service ticket. Events are recorded
 * automatically by model hooks and the status transition flow — never
 * created manually by users, never edited, never deleted.
 */
class ServiceTicketEvent extends Model
{
    protected $fillable = [
        'service_ticket_id',
        'event_type',
        'old_value',
        'new_value',
        'user_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'event_type' => ServiceTicketEventType::class,
        'metadata'   => 'array',
    ];

    /** Single write path for timeline entries; user defaults to whoever is signed in. */
    public static function record(
        int $ticketId,
        ServiceTicketEventType $type,
        ?string $old = null,
        ?string $new = null,
        ?string $notes = null,
        ?array $metadata = null,
    ): self {
        return self::create([
            'service_ticket_id' => $ticketId,
            'event_type'        => $type,
            'old_value'         => $old,
            'new_value'         => $new,
            'user_id'           => auth()->id(),
            'notes'             => $notes,
            'metadata'          => $metadata,
        ]);
    }

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

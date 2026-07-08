<?php

namespace App\Models\Service;

use App\Enums\Service\DiagnosticStepOutcome;
use App\Enums\Service\DiagnosticStepType;
use App\Enums\Service\ServiceTicketEventType;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One diagnostic step a technician took (structured service intelligence).
 * Unlimited steps per ticket; each has a type, findings, and an outcome.
 */
class ServiceTicketDiagnosticStep extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_ticket_id',
        'step_type',
        'description',
        'outcome',
        'created_by',
    ];

    protected $casts = [
        'step_type' => DiagnosticStepType::class,
        'outcome'   => DiagnosticStepOutcome::class,
    ];

    protected static function booted(): void
    {
        static::created(function (self $step) {
            ServiceTicketEvent::record(
                $step->service_ticket_id,
                ServiceTicketEventType::DiagnosticStepAdded,
                notes: sprintf('%s — %s', $step->step_type->label(), $step->outcome->label()),
            );
        });

        static::deleted(function (self $step) {
            ServiceTicketEvent::record(
                $step->service_ticket_id,
                ServiceTicketEventType::DiagnosticStepRemoved,
                notes: $step->step_type->label(),
            );
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

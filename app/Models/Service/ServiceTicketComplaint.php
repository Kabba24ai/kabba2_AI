<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

/**
 * One structured complaint record per selected symptom on a ticket — the
 * unit future diagnostic workflows, root causes, repair actions, and failure
 * analytics will hang off. name/system_group are plain-string snapshots (not
 * enum-cast) so the ticket keeps exactly what was reported even after the
 * library category is renamed, reorganized, or retired — a closed enum
 * would break old rows the moment a category it doesn't know about appears.
 *
 * service_complaint_type_id is the retired library FK (legacy tickets only —
 * left untouched, never written to by new intake); service_symptom_id is its
 * replacement, pointing at the categorized symptom library.
 */
class ServiceTicketComplaint extends Model
{
    protected $fillable = [
        'service_ticket_id',
        'service_complaint_type_id',
        'service_symptom_id',
        'name',
        'system_group',
    ];

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    /** @deprecated Legacy library reference — retained only for tickets created before the symptom library. */
    public function complaintType()
    {
        return $this->belongsTo(ServiceComplaintType::class, 'service_complaint_type_id');
    }

    public function symptom()
    {
        return $this->belongsTo(ServiceSymptom::class, 'service_symptom_id');
    }
}

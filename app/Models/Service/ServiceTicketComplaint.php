<?php

namespace App\Models\Service;

use App\Enums\Service\ComplaintSystemGroup;
use Illuminate\Database\Eloquent\Model;

/**
 * One structured complaint record per selected complaint on a ticket — the
 * unit future diagnostic workflows, root causes, repair actions, and failure
 * analytics will hang off. name/system_group are snapshots so the ticket
 * keeps what was reported even if the library entry is renamed or removed.
 */
class ServiceTicketComplaint extends Model
{
    protected $fillable = [
        'service_ticket_id',
        'service_complaint_type_id',
        'name',
        'system_group',
    ];

    protected $casts = [
        'system_group' => ComplaintSystemGroup::class,
    ];

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function complaintType()
    {
        return $this->belongsTo(ServiceComplaintType::class, 'service_complaint_type_id');
    }
}

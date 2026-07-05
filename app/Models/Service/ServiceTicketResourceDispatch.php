<?php

namespace App\Models\Service;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceTicketResourceDispatch extends Model
{
    protected $fillable = [
        'service_ticket_id',
        'employee_id',
        'vehicle_id',
        'trailer_id',
        'equipment_id',
        'departed_shop_at',
        'arrived_on_site_at',
        'work_started_at',
        'work_completed_at',
        'departed_site_at',
        'returned_to_shop_at',
    ];

    protected $casts = [
        'departed_shop_at'    => 'datetime',
        'arrived_on_site_at'  => 'datetime',
        'work_started_at'     => 'datetime',
        'work_completed_at'   => 'datetime',
        'departed_site_at'    => 'datetime',
        'returned_to_shop_at' => 'datetime',
    ];

    public function serviceTicket()
    {
        return $this->belongsTo(ServiceTicket::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    /** Resources currently out of the shop (dispatched and not yet back). */
    public function scopeCurrentlyOut(Builder $q): Builder
    {
        return $q->whereNotNull('departed_shop_at')->whereNull('returned_to_shop_at');
    }
}

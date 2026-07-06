<?php

namespace App\Models\Service;

use App\Enums\Service\ServiceTicketEventType;
use App\Models\Iam\Personnel\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTicketLaborEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_ticket_id',
        'employee_id',
        'labor_date',
        'start_time',
        'end_time',
        'hours',
        'labor_description',
        'internal_notes',
        'billable',
        'labor_rate',
        'labor_total',
    ];

    protected $casts = [
        'labor_date'  => 'date',
        'hours'       => 'decimal:2',
        'billable'    => 'boolean',
        'labor_rate'  => 'decimal:2',
        'labor_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $entry) {
            // Manually entered hours win; otherwise derive from the time range.
            if (!$entry->hours && $entry->start_time && $entry->end_time) {
                $entry->hours = self::hoursBetween($entry->start_time, $entry->end_time);
            }

            $entry->labor_total = ($entry->hours && $entry->labor_rate !== null)
                ? round((float) $entry->hours * (float) $entry->labor_rate, 2)
                : null;
        });

        static::created(function (self $entry) {
            ServiceTicketEvent::record(
                $entry->service_ticket_id,
                ServiceTicketEventType::LaborAdded,
                notes: sprintf('Labor added: %sh%s', rtrim(rtrim(number_format((float) $entry->hours, 2), '0'), '.'),
                    $entry->employee ? ' — ' . $entry->employee->full_name : ''),
            );
        });

        static::deleted(function (self $entry) {
            ServiceTicketEvent::record(
                $entry->service_ticket_id,
                ServiceTicketEventType::LaborRemoved,
                notes: sprintf('Labor removed: %sh%s', rtrim(rtrim(number_format((float) $entry->hours, 2), '0'), '.'),
                    $entry->employee ? ' — ' . $entry->employee->full_name : ''),
            );
        });
    }

    /** Decimal hours between two H:i(:s) strings; overnight ranges wrap to the next day. */
    public static function hoursBetween(string $start, string $end): float
    {
        $from = Carbon::createFromTimeString($start);
        $to   = Carbon::createFromTimeString($end);
        if ($to->lessThanOrEqualTo($from)) {
            $to->addDay();
        }

        return round($from->diffInMinutes($to) / 60, 2);
    }

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}

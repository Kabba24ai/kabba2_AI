<?php

namespace App\Models\ChecklistManagement\EquipmentChecklist;

use Illuminate\Database\Eloquent\Model;
use App\Models\MaintenanceManagement\Equipment;

use App\Models\Iam\Personnel\User;

class EquipmentStatusLog extends Model
{
    protected $fillable = [
        'equipment_id',
        'from_status',
        'to_status',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Write the durable audit row that EquipmentObserver::updating() would have
     * written on a normal save() — every call site below mutates
     * Equipment.current_status via saveQuietly(), which suppresses that
     * observer. Mirrors the observer's own guard: only log when the status
     * actually changed.
     *
     * Single shared home for this logic — previously copy-pasted into
     * EquipmentStatusService, UpdateProductScheduleController,
     * AssignEquipmentController, RemoveEquipmentController, and Order's
     * deleting hook, which is how the persistStatusLog() call ended up
     * misplaced inside evaluateWaitLists() during a merge (see PR-A1_REVIEW.md).
     */
    public static function recordTransition(int $equipmentId, ?string $from, string $to, ?int $actorId): void
    {
        if ($from === $to) {
            return;
        }

        self::create([
            'equipment_id' => $equipmentId,
            'from_status'  => $from,
            'to_status'    => $to,
            'changed_by'   => $actorId,
            'changed_at'   => now(),
        ]);
    }
}

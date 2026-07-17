<?php

namespace App\Models\Tasks;

use App\Enums\Tasks\TaskCategory;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    protected $table = 'daily_tasks';

    protected $fillable = [
        'category',
        'title',
        'description',
        'priority',
        'status',
        'assigned_to_user_id',
        'created_by_user_id',
        'due_date',
        'related_order_id',
        'related_customer_id',
        'related_supplier_id',
        'related_other',
        'related_equipment_id',
        'completed_at',
        'completed_by_user_id',
    ];

    protected $casts = [
        'category'     => TaskCategory::class,
        'priority'     => TaskPriority::class,
        'status'       => TaskStatus::class,
        'due_date'     => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'related_equipment_id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customers\Customer::class, 'related_customer_id');
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\MaintenanceManagement\Supplier::class, 'related_supplier_id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function media()
    {
        return $this->hasMany(TaskMedia::class, 'task_id');
    }

    /** Attachments on the task itself (not tied to a comment). */
    public function descriptionMedia()
    {
        return $this->hasMany(TaskMedia::class, 'task_id')->whereNull('task_comment_id');
    }

    protected static function booted(): void
    {
        // DB cascade would drop the rows silently; delete through Eloquent
        // so the TaskMedia deleting hook removes the physical files too.
        static::deleting(function (self $task) {
            $task->media()->get()->each->delete();
            \Illuminate\Support\Facades\Storage::disk(TaskMedia::DISK)->deleteDirectory((string) $task->id);
        });
    }

    public function activityLogs()
    {
        return $this->hasMany(TaskActivityLog::class)->latest();
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        // Tasks are date-only (midnight). Overdue threshold = due_date + 17 h (end of shift).
        return $query->whereNotNull('due_date')
            ->whereRaw('DATE_ADD(due_date, INTERVAL ' . self::SHIFT_END_HOUR . ' HOUR) < NOW()')
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->whereDate('due_date', today())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeCompletedToday(Builder $query): Builder
    {
        return $query->where('status', 'completed')
            ->whereDate('completed_at', today());
    }

    // ── Helpers ─────────────────────────────────────────────────────

    // Tasks are created date-only (stored at midnight 00:00:00). They are
    // not considered overdue until end-of-shift on the due date, rather than
    // triggering at midnight when the day starts.
    public const SHIFT_END_HOUR = 17;

    public function isOverdue(): bool
    {
        if (!$this->due_date || $this->status->isTerminal()) {
            return false;
        }
        return $this->due_date->copy()->setTime(self::SHIFT_END_HOUR, 0, 0)->isPast();
    }

    public function logActivity(string $action, ?string $oldValue = null, ?string $newValue = null): void
    {
        $this->activityLogs()->create([
            'user_id'   => auth()->id(),
            'action'    => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}

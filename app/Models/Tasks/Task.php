<?php

namespace App\Models\Tasks;

use App\Enums\Tasks\TaskCategory;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Models\Iam\Personnel\User;
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
        'completed_at',
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

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->latest();
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
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->whereDate('due_date', today())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! $this->status->isTerminal();
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

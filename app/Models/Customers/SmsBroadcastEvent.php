<?php

namespace App\Models\Customers;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An actual SMS broadcast — one send event. Freezes its own copy of the
 * library message, the audience rules used, and (once queued) the exact
 * recipient package. Historical record: completed events are archived,
 * never deleted through the normal interface.
 */
class SmsBroadcastEvent extends Model
{
    protected $fillable = [
        'name',
        'sms_broadcast_id',
        'sms_audience_id',
        'message_name',
        'message_content',
        'category_name',
        'audience_type',
        'positive_mode',
        'include_tag_ids',
        'include_tag_names',
        'exclude_tag_ids',
        'exclude_tag_names',
        'audience_description',
        'recipient_count',
        'sent_count',
        'failed_count',
        'status',
        'wizard_step',
        'queued_at',
        'scheduled_at',
        'sending_started_at',
        'completed_at',
        'cancelled_at',
        'archived_at',
        'created_by',
    ];

    protected $casts = [
        'status'             => SmsBroadcastStatus::class,
        'include_tag_ids'    => 'array',
        'include_tag_names'  => 'array',
        'exclude_tag_ids'    => 'array',
        'exclude_tag_names'  => 'array',
        'queued_at'          => 'datetime',
        'scheduled_at'       => 'datetime',
        'sending_started_at' => 'datetime',
        'completed_at'       => 'datetime',
        'cancelled_at'       => 'datetime',
        'archived_at'        => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(SmsBroadcast::class, 'sms_broadcast_id');
    }

    public function audience()
    {
        return $this->belongsTo(SmsAudience::class, 'sms_audience_id');
    }

    public function recipients()
    {
        return $this->hasMany(SmsBroadcastRecipient::class, 'sms_broadcast_event_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /** The audience spec array the resolver consumes, from the frozen snapshot. */
    public function audienceSpec(): array
    {
        return [
            'base'    => $this->audience_type === 'all' ? 'all' : 'tags',
            'mode'    => $this->positive_mode ?: 'any',
            'include' => $this->include_tag_ids ?? [],
            'exclude' => $this->exclude_tag_ids ?? [],
        ];
    }

    public function audienceSummary(): string
    {
        if ($this->audience_description) {
            return $this->audience_description;
        }

        if ($this->audience_type === 'all') {
            $summary = 'All eligible CRM recipients';
        } else {
            $summary = 'Match ' . strtoupper($this->positive_mode ?: 'any') . ': '
                . implode(', ', $this->include_tag_names ?? []);
        }

        if (!empty($this->exclude_tag_names)) {
            $summary .= ' — excluding ' . implode(', ', $this->exclude_tag_names);
        }

        return $summary;
    }
}

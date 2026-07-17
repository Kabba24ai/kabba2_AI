<?php

namespace App\Models\Customers;

use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Reusable SMS MESSAGE LIBRARY entry — pure content (name, category,
 * body). Never carries send state: actual sends are SmsBroadcastEvent
 * records that freeze their own snapshot of this content. The legacy
 * status/send_date columns are historical leftovers the new UI ignores.
 */
class SmsBroadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'sms_cat_id',
        'name',
        'description',
        'send_date',
        'status',
        'archived_at',
        'created_by',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    // Relationship: each broadcast belongs to a category
    public function category()
    {
        return $this->belongsTo(SmsCategory::class, 'sms_cat_id');
    }

    public function events()
    {
        return $this->hasMany(SmsBroadcastEvent::class, 'sms_broadcast_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}

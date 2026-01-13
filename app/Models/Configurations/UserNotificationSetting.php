<?php

namespace App\Models\Configurations;

use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
     protected $table = 'user_notification_settings';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'type',
    ];

    // expose virtual attribute
    protected $appends = ['source'];

    public function getSourceAttribute()
    {
        return $this->user_id ? 'hrm' : 'manual';
    }
}

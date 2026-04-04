<?php

namespace App\Models\Authrise;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'authorise_submissions';

    protected $fillable = [
        'unique_id',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'password',
        'business_name',
        'street_address',
        'city',
        'state',
        'zip_code',
        'card_name',
        'card_last_four',
        'card_expiry',
        'card_brand',
        'customer_profile_id',
        'payment_profile_id',
        'status',
        'setup_status',
        'comment',
        'amount',
        'schedule_datetime',
        'response_message',
        'meta',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'schedule_datetime' => 'datetime',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $submission): void {
            if (empty($submission->unique_id)) {
                $submission->unique_id = ModelHelper::generateUniqueID($submission, 'AUTH');
            }
        });
    }
}

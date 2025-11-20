<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyContact extends Model
{
    protected $fillable = [
        'user_id',
        'contact_index',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'mobile_phone',
        'phone_number',
        'street_address',
        'city',
        'state',
        'zip_code',
        'country',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

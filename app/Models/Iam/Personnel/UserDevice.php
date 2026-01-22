<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_token',
        'fcm_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}

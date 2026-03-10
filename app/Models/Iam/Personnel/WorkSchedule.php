<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;
use App\Models\Stores\Store;

class WorkSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'store_id',
        'date',
        'start_time',
        'end_time',
        'is_scheduled',
        'hours',
        'notes'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}

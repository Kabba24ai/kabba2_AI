<?php

namespace App\Models\Dispatch;

use Illuminate\Database\Eloquent\Model;

class DispatchAuditLog extends Model
{
    protected $fillable = [
        'order_product_id',
        'action',
        'field',
        'old_value',
        'new_value',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class);
    }
}

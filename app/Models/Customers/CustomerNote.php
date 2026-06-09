<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;
use App\Models\Iam\Personnel\User;


class CustomerNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'description',
        'customer_id',
         'customer_call_needed_id',
        'created_by',
        'created_date',
        'created_time',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate unique_id
        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'CUS-NOTE');
            $model->created_date = $model->created_date ?? now()->toDateString();
            $model->created_time = $model->created_time ?? now()->format('H:i:s');
        });
    }

    public function callNeeded()
{
    return $this->belongsTo(CustomerCallNeeded::class, 'customer_call_needed_id');
}

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}

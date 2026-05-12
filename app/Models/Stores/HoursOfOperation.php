<?php


namespace App\Models\Stores;

use Illuminate\Database\Eloquent\Model;

use App\Helpers\ModelHelper;

class HoursOfOperation extends Model
{
    protected $table = 'hours_of_operation';

    protected $fillable = [
        'unique_id',
        'store_id',
        'day_name',
        'is_closed',
        'start_time',
        'end_time',
          'is_lunch_required',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'STR-HOO');

        });

    }

    // Optional: relation to Store
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}

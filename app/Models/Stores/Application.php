<?php

namespace App\Models\Stores;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ModelHelper;

class Application extends Model
{
    use HasFactory;

    protected $table = 'applications';

    protected $fillable = [
        'unique_id',
        'status',
        'store_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'start_date',
        'personal_details',
        'job_preferences',
        'experience_details',
        'skills',
        'driving_details',
        'resume_media_id',
    ];

    protected $casts = [
        'personal_details' => 'array',
        'job_preferences' => 'array',
        'experience_details' => 'array',
        'skills' => 'array',
        'driving_details' => 'array',
        'start_date' => 'date',
    ];


     public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'EMP-OPP-APP');

        });

    }
}

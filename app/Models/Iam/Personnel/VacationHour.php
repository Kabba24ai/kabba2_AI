<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VacationHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hours',
    ];

    public function users()
    {
        return $this->hasMany(
            User::class,
            'vacation_allotment_hour_id'
        );
    }
}

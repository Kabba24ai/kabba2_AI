<?php

namespace App\Models\Iam\Personnel;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VacationDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'day_number',
    ];

    public function users()
    {
        return $this->hasMany(
            User::class,
            'vacation_start_day_id'
        );
    }
}

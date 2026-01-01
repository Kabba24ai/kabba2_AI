<?php

namespace App\Models\Iam\Personnel;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VacationRequestHour extends Model
{
    protected $fillable = ['name', 'hours'];

    public function vacationRequests()
    {
        return $this->hasMany(
            VacationRequest::class,
            'vacation_request_hour_id'
        );
    }
}


<?php

namespace App\Models\Locations;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'abbreviation'
    ];
}

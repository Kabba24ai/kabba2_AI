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

    public function scopeOrder($query, $direction = 'asc')
    {
        return $query->orderBy('name', $direction);
    }
}

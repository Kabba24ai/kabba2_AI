<?php

namespace App\Models\Configurations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpportunitiesSiteContent extends Model
{
           use HasFactory;

     protected $fillable = [
        'section_key',
        'title',
        'content',
        'is_active'
    ];
}

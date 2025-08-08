<?php
namespace App\Models\Configurations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Color extends Model
{
       use HasFactory;

    protected $fillable = [
        'title',
        'hash_code',
    ];
}
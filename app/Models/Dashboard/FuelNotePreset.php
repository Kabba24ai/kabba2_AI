<?php

namespace App\Models\Dashboard;

use Illuminate\Database\Eloquent\Model;

class FuelNotePreset extends Model
{
    protected $table    = 'fuel_note_presets';
    protected $fillable = ['label'];
}

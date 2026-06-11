<?php

namespace App\Models\Dashboard;

use Illuminate\Database\Eloquent\Model;

class ResolutionNotePreset extends Model
{
    protected $table    = 'resolution_note_presets';
    protected $fillable = ['label'];
}

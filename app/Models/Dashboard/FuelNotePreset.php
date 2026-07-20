<?php

namespace App\Models\Dashboard;

use Illuminate\Database\Eloquent\Model;

class FuelNotePreset extends Model
{
    protected $table    = 'fuel_note_presets';
    protected $fillable = ['label', 'sort_order'];

    /** Managed display order (Note Presets admin page), label as tiebreaker. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }
}

<?php

namespace App\Models\ChecklistManagement\RentalReady;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class RentalReadyChecklistTemplate extends Model
{
    
    use HasFactory;

    protected $fillable = [
        'template_name',
        'description',
        'equipment_category_id',
        'active_template',
        'unique_id',
    ];

    public function questions()
    {
        return $this->hasMany(RentalReadyChecklistTemplateQuestion::class, 'template_id');
    }

}

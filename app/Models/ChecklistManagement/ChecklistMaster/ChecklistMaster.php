<?php


namespace App\Models\ChecklistManagement\ChecklistMaster;

use App\Helpers\ModelHelper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistMaster extends Model
{
    use SoftDeletes , HasFactory;
    protected $table = 'checklist_masters';

    protected $fillable = [
        'unique_id',
        'checklist_system_name',
        'equipment_category_id',
        'rental_ready_template_id',
        'customer_admin_template_id',
    ];

    /**
     * Category relationship
     */
    public function category()
    {
        return $this->belongsTo(\App\Models\ProductManagement\ProductCategory::class, 'equipment_category_id');
    }

    /**
     * Rental Ready Template relationship
     */
    public function rentalReadyTemplate()
    {
        return $this->belongsTo(\App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate::class, 'rental_ready_template_id');
    }

    public function customerAdminTemplate()
    {
        return $this->belongsTo(\App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate::class, 'customer_admin_template_id');
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'CLM'); // Prefix CAT
            }
        });
    }


}

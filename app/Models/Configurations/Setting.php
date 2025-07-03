<?php
namespace App\Models\Configurations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Helpers\ModelHelper;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'setting_type', // e.g., 'Email Settings', 'Product Settings'
        'value_type', // e.g., 'string', 'integer', 'boolean', 'value', 'content'
        'setting_name', // e.g., 'customer_email_send_email_address'
        'setting_title', // e.g., 'Customer Development/Staging Email'
        'setting_value', // e.g., example@example.com
        'setting_options', // For options like dropdowns
        'sort_order',
        'created_by',
        'updated_by',
    ];

   	public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'SET');

            // Set created_by and updated_by
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        // Automatically update updated_by on update
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

     public function setSetting($val){
        $old_value = "";
        if($this->value_type == 'value'){
            $old_value = $this->setting_value;
            $this->setting_value = $val;
        }else{
            $old_value = $this->setting_content;
            $this->setting_content = $val;
        }
        if($this->isDirty()){
            $this->save();
            return [
                'field_name' => $this->setting_name,
                'field_title' => $this->setting_title,
                'old_value' => $old_value,
                'new_value' => $val,
            ];
        }
        return [];
    }

    public function getSetting(){
        return $this->setting_value;
    }
}

<?php
namespace App\Models\Configurations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Helpers\ModelHelper;
use App\Enums\Configurations\SettingType;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'setting_type', // e.g., 'Email Settings', 'Product Settings'
        'value_type', // e.g., 'test', 'number', 'checkbox', 'options', 'email', 'textarea', 'password', 'boolean', 'json'
        'setting_name', // e.g., 'customer_email_send_email_address'
        'setting_title', // e.g., 'Customer Development/Staging Email'
        'setting_value', // e.g., example@example.com
        'setting_options', // For options like dropdowns
        'placeholder',
        'is_secure_field',
        'is_required',
        'is_eye_toggle',
        'is_encrypted',
        'sort_order',
        'created_by',
        'updated_by',
    ];


    //  Auto encrypt/decrypt setting_value
    public function setSettingValueAttribute($value)
    {
        if ($this->is_encrypted) {
            $this->attributes['setting_value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['setting_value'] = $value;
        }
    }

    public function getSettingValueAttribute($value)
    {
        if ($this->is_encrypted && !is_null($value)) {
            try {
                // Try to decrypt
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                // Value wasn't encrypted yet, just return as-is
                return $value;
            }
        }

        if($this->value_type == 'json' && !is_null($value)){
            return json_decode($value, true);
        }

        return $value;
    }


    public function getSettingTypeEnum(): SettingType
    {
        return SettingType::tryFrom($this->setting_type) ?? SettingType::OTHER;
    }


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

    public function getEncryptedValue(): ?string
    {
        return $this->getRawOriginal('setting_value');
    }
}

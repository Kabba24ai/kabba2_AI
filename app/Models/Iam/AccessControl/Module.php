<?php

namespace App\Models\Iam\AccessControl;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Models
use App\Models\Iam\Personnel\User;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'module_category_id',
        'title',
        'name',
        'model_name',
        'permissions',
        'permission_options',
        'sort_order',
        //
        'need_set_permissions', // ['Yes', 'No']
        'permission_updated_at',
        'permission_updated_user_id',
    ];

    protected $table = 'modules';

    // Foreign Ref
    public function category()
    {
        return $this->belongsTo(ModuleCategory::class, 'module_category_id', 'id');
    }

    public function permission_updated_user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function module_permissions()
    {
        return $this->hasMany(Permission::class)->with('permission_roles');
    }

    public function permissions()
    {
        return $this->hasMany('Spatie\Permission\Models\Permission');
    }

    // Scopes
    public function scopeOrder($query)
    {
        return $query->orderBy('need_set_permissions', 'ASC');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'MODL');
        });
    }


    /*
    * Function Name     :   getActivityTitle
    * Use               :   Use for Activity Log
    *
    */
    public function getActivityTitle($add_link = true)
    {
        // route('admin.iam.modules.index')
        if ($add_link) {
            return '<strong><a href="#" target="_blank">' . $this->title . ' [' . $this->unique_id . ']</a></strong>';
        } else {
            return '<strong>' . $this->title . ' [' . $this->unique_id . ']</strong>';
        }
    }
}

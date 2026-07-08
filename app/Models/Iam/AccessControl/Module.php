<?php

namespace App\Models\Iam\AccessControl;

use App\Helpers\ModelHelper;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Models
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'module_category_id',
        'title',
        'name',
        'model_name',
        'permission_names', // This is the renamed column
        'permission_options',
        'sort_order',
        //
        'need_set_permissions', // ['Yes', 'No']
        'permission_updated_at',
        'permission_updated_user_id',
    ];

    protected $table = 'modules';

    /**
     * Platform Sprint 1 (docs/platform/PLATFORM_SPRINT_1_MODULESEEDER.md).
     *
     * `need_set_permissions` is a NOT NULL enum column with no database-level
     * default (see 2025_05_22_112417_create_modules_table.php) and was never
     * supplied by ModuleSeeder's own Module::firstOrCreate() calls either —
     * so creating any module that doesn't already exist has always failed
     * with a NOT NULL constraint violation, reproduced against the
     * untouched, pre-existing 'personnel' module. 'No' is the correct
     * default: ModuleSeeder only ever sets this to 'Yes' when a module's
     * permission set actually changed (see its isDirty() check) — a
     * brand-new module has no such change yet at the moment of creation.
     */
    protected $attributes = [
        'need_set_permissions' => 'No',
    ];

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
            return '<strong><a href="#" >'.$this->title.' ['.$this->unique_id.']</a></strong>';
        } else {
            return '<strong>'.$this->title.' ['.$this->unique_id.']</strong>';
        }
    }
}

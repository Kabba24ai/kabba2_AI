<?php

namespace App\Models\Iam\AccessControl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'name',
        'title',
        'guard_name',
        'permission_to_all',
    ];

    protected $table = 'permissions';

    /**
     * Platform Sprint 1 (docs/platform/PLATFORM_SPRINT_1_MODULESEEDER.md).
     *
     * `permission_to_all` is a NOT NULL enum column with no database-level
     * default (see 2025_05_22_195301_alter_column_to_permissions_table.php)
     * and was never supplied by ModuleSeeder's own Permission::firstOrCreate()
     * calls either — so creating any permission that doesn't already exist
     * has always failed with a NOT NULL constraint violation, reproduced
     * against the untouched, pre-existing 'personnel.*' permissions. 'No' is
     * the correct, safe default — this field is not currently read by any
     * authorization check in this codebase (confirmed via repository-wide
     * search), so this default changes no runtime behavior; it exists so a
     * future feature that does read it starts from the least-privileged
     * state, not an accidental grant-to-everyone.
     */
    protected $attributes = [
        'permission_to_all' => 'No',
    ];

    // Foreign Ref
    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id', 'id');
    }

    public function permission_roles()
    {
        return $this->hasMany(RoleHasPermission::class, 'permission_id', 'id');
    }

    // Scopes
    public function scopeOrder($query)
    {
        return $query->orderBy('id', 'DESC');
    }

    public static function boot()
    {
        parent::boot();
    }
}

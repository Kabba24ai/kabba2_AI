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
    public function scopeOrder($query) {
        return $query->orderBy('id', 'DESC');
    }

    public static function boot()
    {
        parent::boot();
    }
}

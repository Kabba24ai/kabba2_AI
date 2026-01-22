<?php

namespace App\Models\Iam\AccessControl;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;

// Models
use App\Models\Iam\Personnel\User;

class Role extends SpatieRole
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'name',
        'guard_name',
        'short_name',
        'color',
        'description',
        'status', // 'Active','Inactive'
    ];
    protected $table = 'roles';

    // Scopes
    public function scopeOrder($query)
    {
        return $query->orderBy('id', 'ASC');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ROLE');
            $model->short_name = self::generateShortName($model->name);
        });

          // When updating a role
        self::updating(function ($model) {
            $model->short_name = self::generateShortName($model->name);
        });

    }

     // Short name generator
    protected static function generateShortName($name)
    {
        return strtolower(str_replace(' ', '_', trim($name)));
    }

    

}

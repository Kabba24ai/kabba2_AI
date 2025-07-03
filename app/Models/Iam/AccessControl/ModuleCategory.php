<?php

namespace App\Models\Iam\AccessControl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Models
use App\Helpers\ModelHelper;

class ModuleCategory extends Model
{
    use HasFactory;

    use HasFactory;

    protected $fillable = [
        'unique_id',
        'title',
        'sort_order',
    ];

    protected $table = 'module_categories';

    // Foreign Ref
    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    // Scopes
    public function scopeOrder($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'MDCT');
        });
    }
}

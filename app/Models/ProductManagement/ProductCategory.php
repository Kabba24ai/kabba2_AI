<?php

namespace App\Models\ProductManagement;

use App\Helpers\MediaHelper;
use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Cviebrock\EloquentSluggable\Sluggable;

use App\Models\Global\Media;

class ProductCategory extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'unique_id',
        'parent_id',
        'title',
        'slug',
        'short_content',
        'content',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'media_id',
        'status', // 'Active','Inactive'
        'sort_order',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];
    protected $table = 'product_categories';


    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
                'onUpdate' => true,
            ]
        ];
    }

    // Foreign Ref
    public function parentCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id', 'id');
    }

    public function childCategories()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id', 'id');
    }

    public function pageCategoriesBySortOrder()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id', 'id')->orderBy('sort_order', 'ASC');
    }

    // Scopes
    public function scopeOrder($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }
    public function scopeOrderByAdmin($query)
    {
        return $query->orderBy('id', 'Desc');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'PCAT');

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

        static::deleting(function ($model) {
            // Handle any cleanup or related deletions if necessary
            // For example, delete associated media if needed
            if ($model->media) {
                MediaHelper::removeFile($model->media);
                $model->media->delete();
            }
        });
    }
}

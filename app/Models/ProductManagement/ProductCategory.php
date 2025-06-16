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
        'seo_title',
        'seo_description',
        'media_id',
        'status', // Published, Draft, Pending
        'is_featured', // Yes, No
        'sort_order',
        'created_by',
        'updated_by',
    ];
    protected $table = 'product_categories';

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
                'onUpdate' => true,
            ],
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
    public function scopeSortOrder($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }
    public function scopeOrderByAdmin($query)
    {
        return $query->orderBy('title', 'asc');
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

            // If seo_title is not set, use title or slug as fallback
            if (empty($model->seo_title)) {
                $model->seo_title = $model->title ?? $model->slug;
            }

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

    public function products()
    {
        return $this->belongsToMany(Product::class, ProductCategoryChild::class)->withTimestamps();
    }

}

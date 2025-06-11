<?php

namespace App\Models\ProductManagement;

use App\Helpers\MediaHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

use App\Helpers\ModelHelper;
use App\Models\Global\Media;
use App\Models\TermsAndConditions\Terms;

class Product extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'unique_id', // Unique identifier for the product
        'product_name', // Name/title of the product
        'slug', // URL-friendly version of the product name
        'product_type', // Type/category of the product (e.g., retail/rental)
        'seo_title', // SEO-optimized title
        'seo_description', // SEO-optimized meta description
        'short_description', // Short summary/description for listings
        'description', // Full product description
        'media_id', // ID referencing media (image/gallery)
        'is_general_term_type', // Boolean: is this a general term type
        'is_custom_term_type', // Boolean: is this a custom term type

        'sku', // Stock Keeping Unit
        'barcode', // Barcode number
        'retail_price', // Base retail price
        'retail_sale_price', // Discounted sale price (retail)
        'retail_product_cost', // Cost to acquire/make the product

        'rental_daily', // Daily rental rate
        'rental_weekend', // Weekend rental rate
        'rental_weekly', // Weekly rental rate
        'rental_monthly', // Monthly rental rate

        'rental_damage_waiver_daily', // Daily damage waiver fee
        'rental_damage_waiver_weekend', // Weekend damage waiver fee
        'rental_damage_waiver_weekly', // Weekly damage waiver fee
        'rental_damage_waiver_monthly', // Monthly damage waiver fee

        'rental_prepaid_cleaning', // Prepaid cleaning fee
        'rental_prepaid_fuel', // Prepaid fuel fee
        'rental_fuel_gallons', // Number of fuel gallons provided or required
        'rental_fuel_type', // Type of fuel (e.g., gas, diesel)
        'rental_def_gallons', // Diesel Exhaust Fluid (DEF) gallons

        'sale_price_daily', // Sale price if purchased on a daily basis
        'sale_price_weekend', // Sale price for the weekend
        'sale_price_weekly', // Sale price for a week
        'sale_price_monthly', // Sale price for a month

        'standard_delivery_fee',
        'extended_delivery_fee',

        'in_store_pickup', // Boolean: is in-store pickup available
        'delivery_and_pickup', // Boolean: is delivery and pickup available

        'hour_tracking', // Boolean: is hourly usage tracking enabled
        'hour_rate', // Rate per hour if hour tracking is enabled

        'status', // Product status (e.g., Published, Draft)
        'created_by', // ID of the user who created the record
        'updated_by', // ID of the user who last updated the record
    ];

    protected $appends = ['image_url'];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'product_name',
                'onUpdate' => true,
            ],
        ];
    }

    // Scopes
    public function scopeOrder($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'Published');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'PRO');

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
            // Delete child media with proper event triggering
            $model->mediaChildren->each(function ($child) {
                $child->delete(); // Triggers deleting event on ProductMediaChild
            });

            // Detach pivot relationships
            $model->categories()->detach();
            $model->terms()->detach();

            // Delete associated media if needed
            if ($model->media) {
                MediaHelper::removeFile($model->media);
            }
        });
    }

    public function terms()
    {
        return $this->belongsToMany(
            Terms::class,
            'product_terms_children', // pivot table name
            'product_id', // foreign key on pivot for this model
            'terms_and_condition_id', // foreign key on pivot for related model
        );
    }

    public function getImageUrlAttribute()
    {
        return $this->media ? $this->media->getUrl() : asset('storage/admin/images/error/No_Image_Available.jpg');
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id', 'id');
    }

    public function mediaChildren()
    {
        return $this->hasMany(ProductMediaChild::class, 'product_id');
    }

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, ProductCategoryChild::class)->withTimestamps();
    }

    public function options()
    {
        return $this->belongsToMany(ProductOption::class, 'product_option_children', 'product_id', 'product_option_id')->withTimestamps();
    }

    public function relatedProducts()
    {
        return $this->belongsToMany(Product::class, 'product_related_product_children', 'product_id', 'related_product_id')->withTimestamps();
    }
}

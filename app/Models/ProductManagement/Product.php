<?php

namespace App\Models\ProductManagement;

use App\Helpers\MediaHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Str;

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

        'rental_track_insurance_daily', // Daily track insurance fee
        'rental_track_insurance_weekend', // Weekend track insurance fee
        'rental_track_insurance_weekly', // Weekly track insurance fee
        'rental_track_insurance_monthly', // Monthly track insurance fee

        'rental_prepaid_cleaning', // Prepaid cleaning fee
        'rental_prepaid_fuel', // Prepaid fuel fee

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

        'is_default_funnel', // Boolean: is this the default sales funnel
        'has_high_demand_alert', // Boolean: does this product have a high demand alert

        'truck_fee_size_setting', // Size setting for truck fee
        'track_insurance_size_setting', // Size setting for track insurance
        'prepaid_cleaning_rate_setting', // Rate setting for prepaid cleaning
        'prepaid_fuel_rate_setting', // Rate setting for prepaid fuel

        'status', // Product status (e.g., Published, Draft)
        'created_by', // ID of the user who created the record
        'updated_by', // ID of the user who last updated the record
    ];

    protected $appends = [
        'image_url',
        'hover_image_url',
    ];

    protected $casts = [
        'is_default_funnel' => 'boolean',
        'has_high_demand_alert' => 'boolean',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'product_name',
                'onUpdate' => false,
            ],
        ];
    }

    // Scopes
    public function scopeOrder($query)
    {
        return $query->orderBy('product_name', 'ASC');
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

            if (empty($model->seo_description)) {
                $model->seo_description = strip_tags($model->short_description) ?? null;
            }

            // Set created_by and updated_by
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        // Automatically update updated_by on update
        static::updating(function ($model) {

            if (!empty($model->slug)) {
                // Prevent auto-slugging if slug already exists
                $model->slug = Str::slug($model->slug);
            }

            // If seo_title is not set, use title or slug as fallback
            if (empty($model->seo_title)) {
                $model->seo_title = $model->title ?? $model->slug;
            }

            if (empty($model->seo_description)) {
                $model->seo_description = strip_tags($model->short_description) ?? null;
            }

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

    public function getHoverImageUrlAttribute()
    {
        return $this->media ? $this->media->getUrl() : asset('storage/admin/images/error/No_Image_Available.jpg');
    }

    public function getImageUrlAttribute()
    {
        return $this->mediaChildren->first()?->media->url ?? asset('storage/admin/images/error/No_Image_Available.jpg');
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id', 'id');
    }

    public function mediaChildren()
    {
        return $this->hasMany(ProductMediaChild::class, 'product_id')->with('media')->sortOrder();
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
        return  $this->belongsToMany(Product::class, ProductRelatedProductChild::class, 'product_id', 'related_product_id')
                    ->withPivot('sort_order')
                    ->withTimestamps()
                    ->orderBy('pivot_sort_order', 'asc');
    }

    public function getRetailPrice($salePriceFlag = true)
    {
        // If a sale price is set and greater than zero, return it. Otherwise, return retail_price.
        if ($salePriceFlag) {
            return $this->retail_sale_price && $this->retail_sale_price > 0 ? $this->retail_sale_price : $this->retail_price;
        }
        return $this->retail_price;
    }

    public function getRentalPrice($type, $salePriceFlag = true)
    {
        // $type = daily, weekend, weekly, monthly
        $saleField = 'sale_price_' . $type;
        $rentField = 'rental_' . $type;
        if ($salePriceFlag) {
            return $this->$saleField && $this->$saleField > 0 ? $this->$saleField : $this->$rentField;
        }
        return $this->$rentField;
    }

    public function isRentalOnSale($period)
    {
        $saleField = 'sale_price_' . $period;
        return $this->$saleField && $this->$saleField > 0;
    }

    public function isRetailOnSale()
    {
        $saleField = 'retail_sale_price';
        return $this->$saleField && $this->$saleField > 0;
    }

    public function getIsOnSaleAttribute()
    {
        if ($this->product_type === 'Retail') {
            return $this->isRetailOnSale();
        }

        if ($this->product_type === 'Rental') {
            return $this->isRentalOnSale('daily') ||
                   $this->isRentalOnSale('weekend') ||
                   $this->isRentalOnSale('weekly') ||
                   $this->isRentalOnSale('monthly');
        }

        return false;
    }

    public function scopeFilterByPriceType($query, $priceType)
    {
        if ($priceType === 'Sale Price') {
            return $query->where(function ($q) {
                $q->where(function ($retail) {
                    // Retail products with a sale price
                    $retail->where('product_type', 'Retail')->whereNotNull('retail_sale_price')->where('retail_sale_price', '>', 0);
                })->orWhere(function ($rental) {
                    // Rental products with any sale price field > 0
                    $rental->where('product_type', 'Rental')->where(function ($inner) {
                        $inner
                            ->whereNotNull('sale_price_daily')
                            ->where('sale_price_daily', '>', 0)
                            ->orWhere(function ($inner2) {
                                $inner2->whereNotNull('sale_price_weekend')->where('sale_price_weekend', '>', 0);
                            })
                            ->orWhere(function ($inner3) {
                                $inner3->whereNotNull('sale_price_weekly')->where('sale_price_weekly', '>', 0);
                            })
                            ->orWhere(function ($inner4) {
                                $inner4->whereNotNull('sale_price_monthly')->where('sale_price_monthly', '>', 0);
                            });
                    });
                });
            });
        }

        if ($priceType === 'Regular Price') {
            return $query->where(function ($q) {
                $q->where(function ($retail) {
                    // Retail products with no sale price
                    $retail->where('product_type', 'Retail')->where(function ($inner) {
                        $inner->whereNull('retail_sale_price')->orWhere('retail_sale_price', 0);
                    });
                })->orWhere(function ($rental) {
                    // Rental products with no sale price
                    $rental
                        ->where('product_type', 'Rental')
                        ->where(function ($inner) {
                            $inner->whereNull('sale_price_daily')->orWhere('sale_price_daily', 0);
                        })
                        ->where(function ($inner) {
                            $inner->whereNull('sale_price_weekend')->orWhere('sale_price_weekend', 0);
                        })
                        ->where(function ($inner) {
                            $inner->whereNull('sale_price_weekly')->orWhere('sale_price_weekly', 0);
                        })
                        ->where(function ($inner) {
                            $inner->whereNull('sale_price_monthly')->orWhere('sale_price_monthly', 0);
                        });
                });
            });
        }

        return $query;
    }

    public function hasOptions()
    {
        $optionFields = ['rental_prepaid_fuel', 'rental_prepaid_cleaning', 'rental_damage_waiver_daily', 'rental_damage_waiver_weekend', 'rental_damage_waiver_weekly', 'rental_damage_waiver_monthly', 'rental_track_insurance_daily', 'rental_track_insurance_weekend', 'rental_track_insurance_weekly', 'rental_track_insurance_monthly'];

        $hasSettingOption = collect($optionFields)->contains(function ($field) {
            return $this->{$field} !== null;
        });

        $hasCustomOptions =
            $this->options &&
            $this->options
                ->filter(function ($option) {
                    return $option->items && $option->items->isNotEmpty();
                })
                ->isNotEmpty();

        return $hasSettingOption || $hasCustomOptions;
    }
}

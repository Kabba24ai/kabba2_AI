<?php

namespace App\Models\ProductManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;


use App\Models\ICAConfiguration\Setting;
use App\Models\RevenueManagement\CustomerOrderItem;
use App\Models\Activity\ActivityLogItem;

class Product extends Model implements Sortable
{
    use HasFactory, Sluggable, SortableTrait;

    protected $fillable = [
        'legacy_id',
        'unique_id',
        'category_id',
        'sub_category_id',
        'fee_type_id',
        'pict_id',
        'legacy_unique_id',
        'code',
        'title',
        'slug',
        'repayment_prefix',
        'currency', // 'USD', 'PGK'
        'price',
        'priority_processing_status', // 'On', 'Off'
        'priority_processing_price',
        'priority_processing_content',
        'short_content',
        'content',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'status', // 'Active','Inactive'
        'sort_order',
        'created_at',
        'updated_at',
    ];
    protected $table = 'products';

    public $columnTitles = [
        'code' => 'Code',
        'title' => 'Title',
        'slug' => 'Slug',
        'repayment_prefix' => 'Repayment Prefix',
        'currency' => 'Currency',
        'price' => 'Price',
        'priority_processing_status' => 'Priority Processing Status',
        'priority_processing_price' => 'Priority Processing Price',
        'priority_processing_content' => 'Priority Processing Content',
        'short_content' => 'Short Content',
        'content' => 'Content',
        'meta_title' => 'Meta Title',
        'meta_keywords' => 'Meta Keywords',
        'meta_description' => 'Meta Description',
        'status' => 'Status',
    ];

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
    public function order_items()
    {
        return $this->hasMany(CustomerOrderItem::class, 'product_id', 'id');
    }


    public function success_order_items()
    {
        return $this->hasMany(CustomerOrderItem::class, 'product_id', 'id')->whereHas('customer_order', function ($q) {
            $q->where('payment_status', 'Success');
        });;
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'id');
    }

    public function sub_category()
    {
        return $this->belongsTo(ProductCategory::class, 'sub_category_id', 'id');
    }

    public function fee_type()
    {
        return $this->belongsTo(FeeType::class, 'fee_type_id', 'id');
    }

    public function pict()
    {
        return $this->belongsTo(ProductInformationCollectionTemplate::class, 'pict_id', 'id');
    }



    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id', 'id');
    }

    public function activity_log_items_count()
    {
        return ActivityLogItem::where('subject', 'Product')->where('subject_id', $this->id)->count();
    }

    // Scopes
    public function scopeOrder($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = \ModelUtility::generateUniqueID($model, 'PRO');
        });
    }

    /*
    * Function Name     :   getActivityTitle
    * Use               :   Use for Activity Log
    *
    */
    public function getActivityTitle($add_link = true)
    {
        if ($add_link) {
            return '<strong><a href="' . route('admin.product_management.products.show', ['unique_id' => $this->unique_id]) . '" target="_blank">' . $this->title . ' [' . $this->unique_id . ']</a></strong>';
        } else {
            return '<strong>' . $this->title . ' [' . $this->unique_id . ']</strong>';
        }
    }


    public function getProductPricePGK($format = true)
    {
        $setting_item = Setting::where('setting_type', 'Product Settings')->where('setting_name', 'usd_to_pgk')->first();
        $usd_to_pgk = 0;
        if (!is_null($setting_item) && $setting_item->setting_value > 0) {
            $usd_to_pgk = $setting_item->setting_value;
        }

        if ($this->currency == 'USD') {
            if ($format) {
                return number_format($this->price * $usd_to_pgk, 2);
            } else {
                return $this->price * $usd_to_pgk;
            }
        } else {
            if ($format) {
                return number_format($this->price, 2);
            } else {
                return $this->price;
            }
        }
    }

    public function getProductPriorityProcessingPricePGK($format = true)
    {
        $setting_item = Setting::where('setting_type', 'Product Settings')->where('setting_name', 'usd_to_pgk')->first();
        $usd_to_pgk = 0;
        if (!is_null($setting_item) && $setting_item->setting_value > 0) {
            $usd_to_pgk = $setting_item->setting_value;
        }

        if ($this->currency == 'USD') {
            if ($format) {
                return number_format($this->priority_processing_price * $usd_to_pgk, 2);
            } else {
                return $this->priority_processing_price * $usd_to_pgk;
            }
        } else {
            if ($format) {
                return number_format($this->priority_processing_price, 2);
            } else {
                return $this->priority_processing_price;
            }
        }
    }
}

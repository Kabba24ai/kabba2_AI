<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Locations\State;
use App\Helpers\ModelHelper;
use App\Models\Global\Media;


class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'name',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state_id',
        'zip_code',
        'country',
        'tax_id',
        'supplier_category_id',
        'status',
        'payment_terms',
        'tags',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'inside_sales_name',
        'inside_sales_email',
        'inside_sales_phone',
        'technical_support_name',
        'technical_support_email',
        'technical_support_phone',

        'company_logo_media_id',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'SUP');
        });
    }

    /** ---------------------
     *  RELATIONSHIPS
     * --------------------- */

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function category()
    {
        return $this->belongsTo(PartCategory::class, 'supplier_category_id');
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'company_logo_media_id', 'id');
    }

    public function getTagObjectsAttribute()
    {
        // If there are no tags, return an empty collection
        if (empty($this->tags)) {
            return collect();
        }

        // Convert the string to an array of IDs
        $tagIds = array_filter(explode(',', $this->tags));

        // Fetch tag models
        return SupplierTag::whereIn('id', $tagIds)->get();
    }
    public function getLogoUrlAttribute()
    {
        return $this->media ? $this->media->file_url : null;
    }


    /** ---------------------
     *  ACCESSORS
     * --------------------- */

    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            optional($this->state)->name,
            $this->zip_code,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    public function getTagsArrayAttribute()
    {
        return $this->tags ? array_map('trim', explode(',', $this->tags)) : [];
    }

    /** ---------------------
     *  SCOPES
     * --------------------- */

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('primary_contact_name', 'like', "%{$search}%")
                ->orWhere('primary_contact_email', 'like', "%{$search}%");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}

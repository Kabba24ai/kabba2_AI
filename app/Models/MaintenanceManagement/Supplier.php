<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Locations\State;
use App\Helpers\ModelHelper;


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
        'secondary_contact_name',
        'secondary_contact_email',
        'secondary_contact_phone',
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
        return $this->belongsTo(SupplierCategory::class, 'supplier_category_id');
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

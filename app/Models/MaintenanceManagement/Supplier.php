<?php 

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'name',
        'account_number',
        'status',
        'street_address',
        'city',
        'state',
        'zip_code',
        'tax_id',
        'main_phone',
        'main_email',
        'website',
        'sales_name',
        'sales_phone',
        'sales_cell',
        'sales_email',
        'inside_sales_name',
        'inside_sales_phone',
        'inside_sales_cell',
        'inside_sales_email',
        'technical_name',
        'technical_phone',
        'technical_cell',
        'technical_email',
        'parts_name',
        'parts_phone',
        'parts_cell',
        'parts_email',
        'payment_terms',
        'shipping_terms',
        'notes',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($supplier) {
            if (empty($supplier->unique_id)) {
                $supplier->unique_id = Str::uuid();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'unique_id';
    }

    // Relationship with Parts (assuming you have a parts table)
    public function partsAsSupplier()
    {
        return $this->hasMany(Part::class, 'supplier', 'unique_id');
    }

    public function partsAsAlternative1()
    {
        return $this->hasMany(Part::class, 'supplier_alt_1', 'unique_id');
    }

    public function partsAsAlternative2()
    {
        return $this->hasMany(Part::class, 'supplier_alt_2', 'unique_id');
    }

    // Get all parts where this supplier is involved
    public function allParts()
    {
        return Part::where('supplier', $this->unique_id)
                  ->orWhere('supplier_alt_1', $this->unique_id)
                  ->orWhere('supplier_alt_2', $this->unique_id);
    }

    // Get parts count
    public function getPartsCountAttribute()
    {
        return $this->allParts()->count();
    }

    // Get product categories (derived from parts)
    public function getProductCategoriesAttribute()
    {
        // This would be based on your parts categorization system
        // For now, returning empty array - you can implement based on your parts structure
        return [];
    }

    // Full address accessor
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->street_address,
            $this->city,
            $this->state,
            $this->zip_code
        ]);
        
        return implode(', ', $parts);
    }

    // Search scope
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('main_email', 'like', "%{$search}%")
              ->orWhere('sales_name', 'like', "%{$search}%")
              ->orWhere('sales_email', 'like', "%{$search}%");
        });
    }

    // Status scope
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}


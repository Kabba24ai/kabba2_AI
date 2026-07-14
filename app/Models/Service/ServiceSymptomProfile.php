<?php

namespace App\Models\Service;

use App\Enums\Service\ServiceSymptomProfileSymptomMode;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Assembles a machine-specific symptom checklist from whole included
 * categories plus individual additions/exclusions. Resolution precedence at
 * intake: a profile assigned to the equipment's specific product wins over
 * one assigned only to its product category (see BuildsTicketFormData /
 * create.blade.php, where the client performs this same resolution against
 * the selected equipment unit).
 */
class ServiceSymptomProfile extends Model
{
    protected $fillable = [
        'name',
        'product_category_id',
        'product_id',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function profileCategories()
    {
        return $this->hasMany(ServiceSymptomProfileCategory::class, 'service_symptom_profile_id');
    }

    public function profileSymptoms()
    {
        return $this->hasMany(ServiceSymptomProfileSymptom::class, 'service_symptom_profile_id');
    }

    /**
     * The resulting checklist: every active symptom in an included category,
     * plus explicit additions, minus explicit exclusions. Mirrors the JS
     * resolution in create.blade.php — kept here for the future admin
     * "preview the resulting checklist" capability.
     */
    public function resolvedSymptomIds(): Collection
    {
        $categoryIds = $this->profileCategories()->pluck('service_symptom_category_id');
        $additions   = $this->profileSymptoms()->where('mode', ServiceSymptomProfileSymptomMode::Include)->pluck('service_symptom_id');
        $exclusions  = $this->profileSymptoms()->where('mode', ServiceSymptomProfileSymptomMode::Exclude)->pluck('service_symptom_id');

        return ServiceSymptom::active()
            ->where(function ($query) use ($categoryIds, $additions) {
                $query->whereIn('service_symptom_category_id', $categoryIds)
                    ->orWhereIn('id', $additions);
            })
            ->whereNotIn('id', $exclusions->isEmpty() ? [0] : $exclusions)
            ->pluck('id');
    }
}

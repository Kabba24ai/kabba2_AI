<?php

namespace App\Models\Service;

use App\Enums\Service\ComplaintSystemGroup;
use Illuminate\Database\Eloquent\Model;

/**
 * Managed complaint library entry. Complaints identify WHAT is wrong with a
 * machine at intake — never how it will be fixed. Rows carry the metadata a
 * future admin screen will edit: group, capability requirements,
 * product/category applicability, ordering, and active state.
 */
class ServiceComplaintType extends Model
{
    protected $fillable = [
        'name',
        'system_group',
        'required_capabilities',
        'applicable_product_ids',
        'applicable_category_ids',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'system_group'            => ComplaintSystemGroup::class,
        'required_capabilities'   => 'array',
        'applicable_product_ids'  => 'array',
        'applicable_category_ids' => 'array',
        'is_active'               => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

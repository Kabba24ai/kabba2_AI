<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;

class IndexController extends Controller
{
    public function __invoke(string $uniqueId)
    {
        $profile = EquipmentAiProfile::with(['category', 'specifications' => fn ($q) => $q->orderByDesc('is_key_comparison')->orderBy('spec_key')])
            ->where('unique_id', $uniqueId)
            ->firstOrFail();

        $commonSpecKeys = EquipmentAiSpecification::query()
            ->whereHas('profile', fn ($q) => $q->where('category_id', $profile->category_id))
            ->pluck('spec_key')
            ->filter(fn ($key) => filled($key))
            ->map(fn ($key) => trim((string) $key))
            ->flip();

        $keyCriteriaSpecs = $profile->specifications
            ->filter(fn ($spec) => (bool) $spec->is_key_comparison)
            ->values();

        $commonSpecs = $profile->specifications
            ->filter(function ($spec) use ($commonSpecKeys) {
                if ($spec->is_key_comparison) {
                    return false;
                }

                return $commonSpecKeys->has(trim((string) $spec->spec_key));
            })
            ->values();

        $uniqueSpecs = $profile->specifications
            ->filter(function ($spec) use ($commonSpecKeys) {
                if ($spec->is_key_comparison) {
                    return false;
                }

                return ! $commonSpecKeys->has(trim((string) $spec->spec_key));
            })
            ->values();

        return view('admin.maintenance_management.equipment_ai.specifications.index', compact(
            'profile',
            'keyCriteriaSpecs',
            'commonSpecs',
            'uniqueSpecs'
        ));
    }
}

<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\CustomSpec;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpec;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpecValue;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id'       => 'required|exists:product_categories,id',
            'spec_label'        => 'required|string|max:150',
            'description'       => 'nullable|string|max:2000',
            'value_type'        => 'nullable|in:boolean,numeric,text,enum',
            'is_key_comparison' => 'boolean',
            'is_ignored'        => 'boolean',
        ]);

        $categoryId = (int) $data['category_id'];
        $isKey      = (bool) ($data['is_key_comparison'] ?? false);
        $isIgnored  = !$isKey && (bool) ($data['is_ignored'] ?? false);

        // Derive snake_case key from label
        $rawKey = strtolower(trim($data['spec_label']));
        $rawKey = preg_replace('/[^a-z0-9]+/', '_', $rawKey);
        $rawKey = trim($rawKey, '_');

        // Ensure uniqueness within the category
        $key     = $rawKey;
        $attempt = 1;
        while (EquipmentAiCustomSpec::where('category_id', $categoryId)->where('spec_key', $key)->exists()) {
            $key = $rawKey . '_' . (++$attempt);
        }

        $spec = EquipmentAiCustomSpec::create([
            'category_id'       => $categoryId,
            'spec_key'          => $key,
            'spec_label'        => trim($data['spec_label']),
            'description'       => $data['description'] ?? null,
            'value_type'        => $data['value_type'] ?? 'text',
            'allowed_values'    => null,
            'is_key_comparison' => $isKey,
            'is_ignored'        => $isIgnored,
        ]);

        // Seed an "unknown" value row for every profile in this category
        $profileIds = EquipmentAiProfile::where('category_id', $categoryId)
            ->whereHas('specifications')
            ->pluck('id');

        foreach ($profileIds as $profileId) {
            EquipmentAiCustomSpecValue::create([
                'custom_spec_id'          => $spec->id,
                'equipment_ai_profile_id' => $profileId,
                'spec_value'              => null,
                'value_source'            => 'unknown',
                'confirmed_by_user'       => false,
            ]);
        }

        // Sync comparison key table if marked Key
        if ($isKey) {
            EquipmentCategoryComparisonKey::updateOrCreate(
                ['category_id' => $categoryId, 'spec_key' => $key],
                ['display_label' => $spec->spec_label]
            );
        }

        // Build the matrix row descriptor to return to Alpine
        $values    = EquipmentAiCustomSpecValue::where('custom_spec_id', $spec->id)
            ->get()
            ->keyBy('equipment_ai_profile_id')
            ->map(fn ($v) => [
                'id'         => $v->id,
                'value'      => $v->spec_value,
                'source'     => $v->value_source,
                'confidence' => $v->confidence_score,
                'confirmed'  => $v->confirmed_by_user,
                'reason'     => $v->ai_reason,
            ]);

        $presence = $values->mapWithKeys(fn ($v, $pid) => [$pid => $v['value'] !== null]);

        return response()->json([
            'success' => true,
            'row' => [
                'spec_key'          => $spec->spec_key,
                'spec_label'        => $spec->spec_label,
                'description'       => $spec->description,
                'is_key_comparison' => $spec->is_key_comparison,
                'is_ignored'        => $spec->is_ignored,
                'is_custom'         => true,
                'custom_spec_id'    => $spec->id,
                'value_type'        => $spec->value_type,
                'allowed_values'    => $spec->allowed_values ?? [],
                'values'            => $values->toArray(),
                'presence'          => $presence->toArray(),
                'presence_count'    => $presence->filter()->count(),
            ],
        ]);
    }
}

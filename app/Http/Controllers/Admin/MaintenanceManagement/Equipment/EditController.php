<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;
use App\Models\MaintenanceManagement\PartsList;
use Illuminate\Support\Str;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification\GenerateController as SpecFormatter;
use App\Models\ProductManagement\Product;

class EditController extends Controller
{
    public function __invoke($unique_id)
    {
        $equipment = Equipment::with(['serviceTemplate', 'documentImages.media', 'partsList', 'productCategory', 'specifications.approvedByUser'])
            ->where('unique_id', $unique_id)
            ->firstOrFail();
        $categories = ProductCategory::getHierarchy();
        $checklistMasters = ChecklistMaster::oldest('checklist_system_name')->pluck('checklist_system_name', 'id')->prepend('Select Checklist Template', '');

        $partsLists = PartsList::orderBy('name')->pluck('name', 'id')->prepend('Select Parts List Template', '');

        $stores = Store::pluck('store_name', 'id')->toArray();
        $serviceTemplates = ServiceTemplate::oldest('name')->pluck('name', 'id')->toArray();

        //        $selectedPartsListIds = PartsList::whereJsonContains(
        //     'selected_products',
        //     (string) $equipment->id
        // )->pluck('id')->toArray();
        // dd($selectedPartsListIds);

        $selectedPartsListId = $equipment->parts_list_id;
        $partListUniqueId = $equipment->partsList?->unique_id;

        $similarEquipmentCandidates = Equipment::query()
            ->where('id', '!=', $equipment->id)
            ->where('product_category_id', $equipment->product_category_id)
            ->orderBy('equipment_name')
            ->get(['id', 'equipment_name', 'equipment_id', 'brand', 'model']);

        $similarEquipmentOptions = $similarEquipmentCandidates
            ->mapWithKeys(function ($item) {
                $label = $item->equipment_name;
                if (!empty($item->equipment_id)) {
                    $label .= ' (' . $item->equipment_id . ')';
                }

                return [$item->id => [
                    'label' => $label,
                    'brand' => $item->brand ?? null,
                    'model' => $item->model ?? null,
                ]];
            })
            ->toArray();

        $normalizedCurrentName = Str::of((string) $equipment->equipment_name)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->value();

        $currentKeywords = collect(explode(' ', $normalizedCurrentName))
            ->filter(fn($word) => strlen($word) >= 3)
            ->values();

        $defaultSimilarEquipmentIds = $similarEquipmentCandidates
            ->filter(function ($item) use ($normalizedCurrentName, $currentKeywords) {
                $normalizedCandidateName = Str::of((string) $item->equipment_name)
                    ->lower()
                    ->replaceMatches('/[^a-z0-9\s]/', ' ')
                    ->squish()
                    ->value();

                if ($normalizedCandidateName === '' || $normalizedCurrentName === '') {
                    return false;
                }

                if (str_contains($normalizedCandidateName, $normalizedCurrentName) || str_contains($normalizedCurrentName, $normalizedCandidateName)) {
                    return true;
                }

                $candidateKeywords = collect(explode(' ', $normalizedCandidateName))
                    ->filter(fn($word) => strlen($word) >= 3);

                $overlapCount = $currentKeywords->intersect($candidateKeywords)->count();

                return $overlapCount >= 2 || ($currentKeywords->count() === 1 && $overlapCount === 1);
            })
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->values()
            ->toArray();

        $criteriaRows = EquipmentAiProfile::query()
            ->where('category_id', $equipment->product_category_id)
            ->with(['specifications' => fn($q) => $q->orderByDesc('is_key_comparison')->orderBy('spec_key')])
            ->get()
            ->map(function ($profile) {
                return [
                    'profile_id' => $profile->id,
                    'profile_unique_id' => $profile->unique_id,
                    'profile_name' => $profile->name,
                    'specifications' => $profile->specifications->map(function ($spec) {
                        return [
                            'id' => $spec->id,
                            'spec_key' => $spec->spec_key,
                            'spec_value' => $spec->spec_value,
                            'is_key_comparison' => (bool) $spec->is_key_comparison,
                        ];
                    }),
                ];
            });

        $matchingAiProfile = null;
        $keyCriteriaSpecs = collect();
        $commonSpecs = collect();
        $uniqueSpecs = collect();

        $equipmentMake = trim((string) $equipment->brand);
        $equipmentModel = trim((string) $equipment->model);

        if ($equipmentMake !== '') {
            $normalizedMake = EquipmentAiProfile::normalize($equipmentMake);
            $normalizedModel = EquipmentAiProfile::normalize($equipmentModel);

            $matchingAiProfile = EquipmentAiProfile::with(['specifications' => fn($q) => $q->orderByDesc('is_key_comparison')->orderBy('spec_key')])
                ->where('category_id', $equipment->product_category_id)
                ->where('normalized_make', $normalizedMake)
                ->where('normalized_model', $normalizedModel)
                ->first();

            if ($matchingAiProfile) {
                $categorySpecKeys = EquipmentAiProfile::query()
                    ->where('category_id', $equipment->product_category_id)
                    ->with('specifications:id,equipment_ai_profile_id,spec_key')
                    ->get()
                    ->pluck('specifications')
                    ->flatten()
                    ->pluck('spec_key')
                    ->filter(fn($key) => filled($key))
                    ->map(fn($key) => trim((string) $key))
                    ->flip();

                $keyCriteriaSpecs = $matchingAiProfile->specifications
                    ->filter(fn($spec) => (bool) $spec->is_key_comparison)
                    ->values();

                $commonSpecs = $matchingAiProfile->specifications
                    ->filter(function ($spec) use ($categorySpecKeys) {
                        if ($spec->is_key_comparison) {
                            return false;
                        }

                        return $categorySpecKeys->has(trim((string) $spec->spec_key));
                    })
                    ->values();

                $uniqueSpecs = $matchingAiProfile->specifications
                    ->filter(function ($spec) use ($categorySpecKeys) {
                        if ($spec->is_key_comparison) {
                            return false;
                        }

                        return ! $categorySpecKeys->has(trim((string) $spec->spec_key));
                    })
                    ->values();
            }
        }

        $comparableAiProfiles = EquipmentAiProfile::query()
            ->withCount('specifications')
            ->withCount('keySpecifications')
            ->where('category_id', $equipment->product_category_id)
            ->orderBy('make')
            ->orderBy('model')
            ->get();

        $selectedComparableAiProfileIds = collect(old(
            'comparable_ai_profile_ids',
            $equipment->comparable_ai_profile_ids ?? []
        ))
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $productAssignmentOptions = Product::query()
            ->whereHas('categories', function ($query) use ($equipment) {
                $query->where('product_categories.id', $equipment->product_category_id);
            })
            ->orderBy('product_name', 'asc')
            ->get(['id', 'product_name'])
            ->map(function ($product) {
                return [
                    'id' => (int) $product->id,
                    'label' => (string) $product->product_name,
                ];
            })
            ->values()
            ->toArray();

        return view('admin.maintenance_management.equipment.edit', compact(
            'equipment',
            'categories',
            'checklistMasters',
            'stores',
            'serviceTemplates',
            'partsLists',
            'selectedPartsListId',
            'similarEquipmentOptions',
            'defaultSimilarEquipmentIds',
            'selectedComparableAiProfileIds',
            'criteriaRows',
            'comparableAiProfiles',
            'productAssignmentOptions',
            'matchingAiProfile',
            'keyCriteriaSpecs',
            'commonSpecs',
            'uniqueSpecs'
        ) + [
            'equipmentLookupPayload' => json_encode([
                'brand'          => $equipment->brand ?? '',
                'model'          => $equipment->model ?? '',
                'model_year'     => $equipment->model_year,
                'category'       => optional($equipment->productCategory)->title ?? '',
                'equipment_name' => $equipment->equipment_name ?? '',
                'equipment_id'   => $equipment->equipment_id ?? '',
                'serial_number'  => $equipment->serial_number ?? null,
                'vin'            => $equipment->vehicle_identification_number ?? null,
            ], JSON_PRETTY_PRINT),
        ]);
    }
}

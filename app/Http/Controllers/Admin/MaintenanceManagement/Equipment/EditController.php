<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;
use App\Models\MaintenanceManagement\PartsList;
use Illuminate\Support\Str;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification\GenerateController as SpecFormatter;

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
            ->get(['id', 'equipment_name', 'equipment_id']);

        $similarEquipmentOptions = $similarEquipmentCandidates
            ->mapWithKeys(function ($item) {
                $label = $item->equipment_name;
                if (!empty($item->equipment_id)) {
                    $label .= ' (' . $item->equipment_id . ')';
                }

                return [$item->id => $label];
            })
            ->toArray();

        $normalizedCurrentName = Str::of((string) $equipment->equipment_name)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->value();

        $currentKeywords = collect(explode(' ', $normalizedCurrentName))
            ->filter(fn ($word) => strlen($word) >= 3)
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
                    ->filter(fn ($word) => strlen($word) >= 3);

                $overlapCount = $currentKeywords->intersect($candidateKeywords)->count();

                return $overlapCount >= 2 || ($currentKeywords->count() === 1 && $overlapCount === 1);
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->toArray();

        return view('admin.maintenance_management.equipment.edit', compact(
            'equipment', 'categories', 'checklistMasters', 'stores', 'serviceTemplates',
            'partsLists', 'selectedPartsListId', 'similarEquipmentOptions', 'defaultSimilarEquipmentIds'
        ) + [
            'existingSpecs' => $equipment->specifications->map(fn ($s) => SpecFormatter::formatSpec($s))->values()->toJson(),
            'specGenerateUrl' => route('admin.maintenance-management.equipment.specification.generate', $equipment->unique_id),
            'specApproveBaseUrl' => Str::beforeLast(route('admin.maintenance-management.equipment.specification.generate', $equipment->unique_id), '/generate'),
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

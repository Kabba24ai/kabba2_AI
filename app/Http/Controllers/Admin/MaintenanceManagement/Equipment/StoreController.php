<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\StoreRequest;
use App\Models\MaintenanceManagement\Equipment;
 use App\Models\MaintenanceManagement\PartsList;
use App\Models\MaintenanceManagement\EquipmentMedia;
use App\Helpers\MediaHelper;


class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        $data['has_def'] = ($data['has_def'] ?? false) ? 'Yes' : 'No';
        if (($data['overage_rate'] ?? null) === '') {
            $data['overage_rate'] = null;
        }
        $data['is_tracked'] = isset($data['overage_rate']) && $data['overage_rate'] !== null && $data['overage_rate'] !== ''
            ? 'Yes'
            : 'No';
        $data['not_for_rent'] = isset($data['not_for_rent']) ? 1 : 0;

        // If bring_service_flag is checked and bring_service_hour is not null, set equipment_hours to bring_service_hour
        if (isset($data['bring_service_flag']) && $data['bring_service_flag'] && isset($data['bring_service_hour']) && $data['bring_service_hour'] !== null) {
            $data['equipment_hours'] = $data['bring_service_hour'];
        }

      $equipment =  Equipment::create($data);

        if ($request->hasFile('document_images')) {
            foreach ($request->file('document_images') as $file) {
                $mediaData = MediaHelper::uploadStorageFile('Public Asset', $file, 'equipment/documents', $equipment);
                if (!empty($mediaData['mediaObj'])) {
                    EquipmentMedia::create([
                        'equipment_id' => $equipment->id,
                        'media_id' => $mediaData['mediaObj']->id,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            }
        }


      // Assign equipment to ONE parts list
        if (!empty($data['parts_list_id'])) {

            $list = PartsList::find($data['parts_list_id']);

            if ($list) {
                $equipmentId = (string) $equipment->id;

                $products = array_map('strval', $list->selected_products ?? []);

                if (!in_array($equipmentId, $products, true)) {
                    $products[] = $equipmentId;
                }

                $list->update([
                    'selected_products' => array_values(array_unique($products)),
                ]);
            }
        }


  //  ALWAYS STRING
        // $equipmentId = (string) $equipment->id;

        // $partsListIds = $data['parts_lists'] ?? [];

        // foreach ($partsListIds as $listId) {
        //     $list = PartsList::find($listId);
        //     if (!$list) continue;

        //     $products = array_map('strval', $list->selected_products ?? []);

        //     if (!in_array($equipmentId, $products, true)) {
        //         $products[] = $equipmentId;
        //     }

        //     $list->update([
        //         'selected_products' => array_values(array_unique($products)),
        //     ]);
        // }

        return redirect()
            ->route('admin.maintenance-management.equipment.index')
            ->with('success', 'Equipment created successfully!');

    }
}

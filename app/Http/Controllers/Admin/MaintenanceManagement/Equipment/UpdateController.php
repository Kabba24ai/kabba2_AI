<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\UpdateRequest;
use App\Models\MaintenanceManagement\Equipment;
 use App\Models\MaintenanceManagement\PartsList;
use App\Models\MaintenanceManagement\EquipmentMedia;
use App\Helpers\MediaHelper;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $unique_id)
    {

        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();

        $equipmentId = (string) $equipment->id;
        $oldPartsListId = $equipment->parts_list_id;
        $newPartsListId = $request->input('parts_list_id');

        $data = $request->validated();
        $data['has_def'] = ($data['has_def'] ?? false) ? 'Yes' : 'No';
        $data['is_tracked'] = ($data['is_tracked'] ?? false) ? 'Yes' : 'No';
        $data['not_for_rent'] = isset($data['not_for_rent']) ? 1 : 0;

        // If bring_service_flag is checked and bring_service_hour is not null, set equipment_hours to bring_service_hour
        if (isset($data['bring_service_flag']) && $data['bring_service_flag'] && isset($data['bring_service_hour']) && $data['bring_service_hour'] !== null) {
            $data['equipment_hours'] = $data['bring_service_hour'];
        }

        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();
        $equipment->update($data);

        $removeIds = $request->input('document_images_remove', []);
        if (!empty($removeIds)) {
            EquipmentMedia::where('equipment_id', $equipment->id)
                ->whereIn('id', $removeIds)
                ->get()
                ->each(function ($document) {
                    $document->delete();
                });
        }

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

        /**
         * ---------------------------------------------------------
         * REMOVE from OLD parts list (if changed)
         * ---------------------------------------------------------
         */
        if ($oldPartsListId && $oldPartsListId != $newPartsListId) {
            $oldList = PartsList::find($oldPartsListId);

            if ($oldList) {
                $products = array_map('strval', $oldList->selected_products ?? []);
                $products = array_values(array_diff($products, [$equipmentId]));

                $oldList->update([
                    'selected_products' => $products
                ]);
            }
        }

        /**
         * ---------------------------------------------------------
         * ADD to NEW parts list
         * ---------------------------------------------------------
         */
        if ($newPartsListId) {
            $newList = PartsList::find($newPartsListId);

            if ($newList) {
                $products = array_map('strval', $newList->selected_products ?? []);

                if (!in_array($equipmentId, $products, true)) {
                    $products[] = $equipmentId;
                }

                $newList->update([
                    'selected_products' => array_values(array_unique($products))
                ]);
            }
        }

        return redirect()
            ->route('admin.maintenance-management.equipment.index')
            ->with('success', 'Equipment updated successfully!');
    }
}

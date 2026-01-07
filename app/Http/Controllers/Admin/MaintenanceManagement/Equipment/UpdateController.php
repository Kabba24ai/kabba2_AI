<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\UpdateRequest;
use App\Models\MaintenanceManagement\Equipment;
 use App\Models\MaintenanceManagement\PartsList;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $unique_id)
    {


    //      $oldequipment = Equipment::where('unique_id', $unique_id)->firstOrFail();

    //   $equipmentId = (string) $oldequipment->id;

    // $oldPartsListIds = PartsList::whereJsonContains(
    //     'selected_products',
    //     $equipmentId
    // )->pluck('id')->toArray();


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


         // Extract new selections
        // $newPartsListIds = $data['parts_lists'] ?? [];
          /**
         * REMOVE unselected
         */
        // $toRemove = array_diff($oldPartsListIds, $newPartsListIds);

        // foreach ($toRemove as $listId) {
        //     $list = PartsList::find($listId);
        //     if (!$list) continue;

        //     $products = array_map('strval', $list->selected_products ?? []);
        //     $products = array_values(array_diff($products, [$equipmentId]));

        //     $list->update(['selected_products' => $products]);
        // }

        // /**
        //  * ADD newly selected
        //  */
        // $toAdd = array_diff($newPartsListIds, $oldPartsListIds);

        // foreach ($toAdd as $listId) {
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

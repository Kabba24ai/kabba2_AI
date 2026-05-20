<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\UpdateRequest;
use App\Models\MaintenanceManagement\Equipment;
 use App\Models\MaintenanceManagement\PartsList;
use App\Models\MaintenanceManagement\EquipmentMedia;
use App\Helpers\MediaHelper;
use App\Helpers\ModelHelper;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $unique_id)
    {
        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();

        // Prepare validated data
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

        $data['allow_upgrades'] = filter_var($data['allow_upgrades'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $data['allow_downgrades'] = filter_var($data['allow_downgrades'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $data['downgrade_requires_approval'] = filter_var($data['downgrade_requires_approval'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $data['similar_equipment_ids'] = array_values(array_map('intval', (array) ($data['similar_equipment_ids'] ?? [])));

        $criteriaInput = (array) ($data['critical_matching_criteria'] ?? []);
        $normalizedCriteria = [];

        foreach ((array) $criteriaInput as $key => $item) {
            if (!is_array($item)) {
                continue;
            }

            $thresholdRaw = $item['threshold'] ?? null;
            $threshold = ($thresholdRaw === '' || $thresholdRaw === null)
                ? null
                : (float) $thresholdRaw;

            $weightRaw = $item['weight'] ?? 50;
            $weight = max(0, min(100, (int) $weightRaw));

            $normalizedCriteria[(string) $key] = [
                'enabled' => filter_var($item['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'threshold' => $threshold,
                'weight' => $weight,
                'upgrade_exceeds_value' => filter_var($item['upgrade_exceeds_value'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'caution_if_change_value' => filter_var($item['caution_if_change_value'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'upgrade_is_below_value' => filter_var($item['upgrade_is_below_value'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'caution_if_below_value' => filter_var($item['caution_if_below_value'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $data['critical_matching_criteria'] = $normalizedCriteria;

        // =====================================
        // CHECK IF SAVE AS NEW
        // =====================================
        if ($request->has('save_as_new')) {
            // Load equipment with relationships for copying documents
            $equipment = Equipment::with(['documentImages.media'])
                ->where('unique_id', $unique_id)
                ->firstOrFail();

            // Create a copy
            $equipmentCopy = $equipment->replicate();

            // Generate new unique ID
            $equipmentCopy->unique_id = ModelHelper::generateUniqueID(new Equipment, 'EQP');

            // Add "Copy of " prefix to equipment name (from form or original)
            if (isset($data['equipment_name'])) {
                $equipmentCopy->equipment_name = 'Copy of ' . $data['equipment_name'];
            } else {
                $equipmentCopy->equipment_name = 'Copy of ' . $equipment->equipment_name;
            }

            // Generate a unique suffix for equipment_id
            $suffix = strtoupper(substr(uniqid(), -4)); // e.g. "A3F9"

            // Get equipment_id from form data or original
            $originalEquipmentId = $data['equipment_id'] ?? $equipment->equipment_id;
            $equipmentCopy->equipment_id = $originalEquipmentId . '-Copy-' . $suffix;

            // Apply all form changes to the copy
            foreach ($data as $key => $value) {
                if ($key !== 'equipment_name' && $key !== 'equipment_id' && $key !== 'document_images' && $key !== 'document_images_remove') {
                    $equipmentCopy->$key = $value;
                }
            }

            // Save the new equipment copy
            $equipmentCopy->save();

            // Copy document images - create new physical files and records
            if ($equipment->documentImages->isNotEmpty()) {
                foreach ($equipment->documentImages as $documentImage) {
                    if ($documentImage->media) {
                        // Copy the actual file on disk
                        $copyResult = MediaHelper::copyExistingMediaOnDisk(
                            $documentImage->media,
                            $documentImage->media->asset_type ?? 'Public Asset',
                            'equipment',
                            $equipmentCopy,
                            'Yes'
                        );

                        // Create new equipment_media record if copy succeeded
                        if (isset($copyResult['mediaObj'])) {
                            EquipmentMedia::create([
                                'equipment_id' => $equipmentCopy->id,
                                'media_id' => $copyResult['mediaObj']->id,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]);
                        }
                    }
                }
            }

            // Handle new document uploads for the copy
            if ($request->hasFile('document_images')) {
                foreach ($request->file('document_images') as $file) {
                    $mediaData = MediaHelper::uploadStorageFile('Public Asset', $file, 'equipment/documents', $equipmentCopy);
                    if (!empty($mediaData['mediaObj'])) {
                        EquipmentMedia::create([
                            'equipment_id' => $equipmentCopy->id,
                            'media_id' => $mediaData['mediaObj']->id,
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);
                    }
                }
            }

            // Handle parts list relationship for the copy
            $newPartsListId = $equipmentCopy->parts_list_id;
            if ($newPartsListId) {
                $newList = PartsList::find($newPartsListId);
                if ($newList) {
                    $products = array_map('strval', $newList->selected_products ?? []);
                    $equipmentCopyId = (string) $equipmentCopy->id;
                    if (!in_array($equipmentCopyId, $products, true)) {
                        $products[] = $equipmentCopyId;
                    }
                    $newList->update([
                        'selected_products' => array_values(array_unique($products))
                    ]);
                }
            }

            // Redirect to EDIT page of the new equipment copy
            return redirect()->route(
                'admin.maintenance-management.equipment.edit',
                $equipmentCopy->unique_id
            )->with('success', 'Equipment saved as new with all changes applied and documents copied!');
        }

        // =====================================
        // NORMAL UPDATE (NOT SAVE AS NEW)
        // =====================================
        $equipmentId = (string) $equipment->id;
        $oldPartsListId = $equipment->parts_list_id;
        $newPartsListId = $request->input('parts_list_id');

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

        $action = $request->input('action', 'save');

        if ($action === 'save_exit') {
            return redirect()
                ->route('admin.maintenance-management.equipment.index')
                ->with('success', 'Equipment updated successfully!');
        }

        return redirect()
            ->route('admin.maintenance-management.equipment.edit', $equipment->unique_id)
            ->with('success', 'Equipment updated successfully!');
    }
}

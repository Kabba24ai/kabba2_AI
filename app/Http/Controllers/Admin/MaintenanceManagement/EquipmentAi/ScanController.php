<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\ProductManagement\ProductCategory;

class ScanController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
        ]);

        $categoryId = $request->category_id;

        // Pull all equipment in this category that have a brand/model value
        $equipment = Equipment::where('product_category_id', $categoryId)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->get(['id', 'brand', 'model']);

        if ($equipment->isEmpty()) {
            return redirect()
                ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
                ->with('error', 'No equipment with brand/model data found in this category.');
        }

        $created = 0;
        $skipped = 0;

        foreach ($equipment as $item) {
            $make  = trim($item->brand ?? '');
            $model = trim($item->model ?? '');

            if ($make === '') {
                $skipped++;
                continue;
            }

            $normalizedMake  = EquipmentAiProfile::normalize($make);
            $normalizedModel = EquipmentAiProfile::normalize($model);

            // firstOrCreate avoids duplicates — the unique index is the safety net
            $profile = EquipmentAiProfile::firstOrCreate(
                [
                    'category_id'      => $categoryId,
                    'normalized_make'  => $normalizedMake,
                    'normalized_model' => $normalizedModel,
                ],
                [
                    'make'      => $make,
                    'model'     => $model,
                    'ai_status' => 'pending',
                ]
            );

            if ($profile->wasRecentlyCreated) {
                $created++;
            } else {
                $skipped++;
            }
        }

        $category = ProductCategory::find($categoryId);

        $message = 'Scan complete for "' . $category->title . '": '
            . $created . ' new profile(s) created, ' . $skipped . ' duplicate(s) skipped.';

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
            ->with('success', $message);
    }
}

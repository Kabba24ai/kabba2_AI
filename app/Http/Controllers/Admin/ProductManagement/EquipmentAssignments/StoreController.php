<?php

namespace App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductEquipmentAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoreController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id|unique:product_equipment_assignments,product_id',
            'selected_category_id' => 'nullable|exists:product_categories,id',
            'upgrade_primary_category_id' => 'nullable|exists:product_categories,id',
            'upgrade_alt1_category_id' => 'nullable|exists:product_categories,id',
            'upgrade_alt2_category_id' => 'nullable|exists:product_categories,id',
            'downgrade_opt1_category_id' => 'nullable|exists:product_categories,id',
            'primary_equipment_pool' => 'nullable|array',
            'primary_equipment_pool.*' => 'exists:equipment,id',
            'upgrade_path_primary' => 'nullable|array',
            'upgrade_path_primary.*' => 'exists:equipment,id',
            'upgrade_path_alternate_1' => 'nullable|array',
            'upgrade_path_alternate_1.*' => 'exists:equipment,id',
            'upgrade_path_alternate_2' => 'nullable|array',
            'upgrade_path_alternate_2.*' => 'exists:equipment,id',
            'downgrade_path_option_1' => 'nullable|array',
            'downgrade_path_option_1.*' => 'exists:equipment,id',
            'assignment_notes' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $this->ensureNoDuplicateEquipmentAcrossPaths($validated);

        DB::transaction(function () use ($validated) {
            $assignment = ProductEquipmentAssignment::create([
                'product_id' => $validated['product_id'],
                'base_product_category_id' => $validated['selected_category_id'] ?? null,
                'assignment_notes' => $validated['assignment_notes'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $assignment->syncPath(
                ProductEquipmentAssignment::PATH_PRIMARY_POOL,
                $validated['selected_category_id'] ?? null,
                $validated['primary_equipment_pool'] ?? []
            );

            $assignment->syncPath(
                ProductEquipmentAssignment::PATH_UPGRADE_PRIMARY,
                $validated['upgrade_primary_category_id'] ?? null,
                $validated['upgrade_path_primary'] ?? []
            );

            $assignment->syncPath(
                ProductEquipmentAssignment::PATH_UPGRADE_ALTERNATE_1,
                $validated['upgrade_alt1_category_id'] ?? null,
                $validated['upgrade_path_alternate_1'] ?? []
            );

            $assignment->syncPath(
                ProductEquipmentAssignment::PATH_UPGRADE_ALTERNATE_2,
                $validated['upgrade_alt2_category_id'] ?? null,
                $validated['upgrade_path_alternate_2'] ?? []
            );

            $assignment->syncPath(
                ProductEquipmentAssignment::PATH_DOWNGRADE_OPTION_1,
                $validated['downgrade_opt1_category_id'] ?? null,
                $validated['downgrade_path_option_1'] ?? []
            );
        });

        return redirect()
            ->route('admin.product-management.equipment-assignments.index')
            ->with('success', 'Equipment assignment created successfully.');
    }

    private function ensureNoDuplicateEquipmentAcrossPaths(array $validated): void
    {
        $pathFields = [
            'primary_equipment_pool',
            'upgrade_path_primary',
            'upgrade_path_alternate_1',
            'upgrade_path_alternate_2',
            'downgrade_path_option_1',
        ];

        $seen = [];
        foreach ($pathFields as $field) {
            foreach (($validated[$field] ?? []) as $equipmentId) {
                $equipmentId = (int) $equipmentId;
                if (isset($seen[$equipmentId])) {
                    throw ValidationException::withMessages([
                        $field => 'Each equipment item can be selected in only one path.',
                    ]);
                }
                $seen[$equipmentId] = $field;
            }
        }
    }
}

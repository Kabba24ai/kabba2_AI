<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\UpdateRequest;
use App\Models\MaintenanceManagement\Part;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $unique_id)
    {

        $part = Part::where('unique_id',$unique_id)->firstOrFail();
       
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $partData = [
                'part_name'             => $validated['part_name'],
                'description'           => $validated['part_description'] ?? null,
                'stock_level'           => $validated['current_stock'] ?? 0,
                'min_stock'             => $validated['min_stock'] ?? 0,
                'dni'                   => $validated['dni'] ?? false,
                'general_supply_item'   => $validated['gsi'] ?? false,
                'is_active'             => true,

                // Primary
                'primary_part_number'   => $validated['part_number'],
                'primary_part_cost'     => $validated['unit_cost'],
                'primary_part_supplier_id' => $validated['supplier'],

                // Alt 1
                'alt_1_part_number'     => $validated['part_number_alt_1'] ?? null,
                'alt_1_part_cost'       => $validated['cost_alt_1'] ?? 0,
                'alt_1_part_supplier_id' => $validated['supplier_alt_1'] ?? null,

                // Alt 2
                'alt_2_part_number'     => $validated['part_number_alt_2'] ?? null,
                'alt_2_part_cost'       => $validated['cost_alt_2'] ?? 0,
                'alt_2_part_supplier_id' => $validated['supplier_alt_2'] ?? null,
            ];

            $part->update($partData);

            DB::commit();

            return redirect()
                ->route('admin.maintenance-management.parts.index')
                ->with('success', 'Part updated successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Something went wrong while updating the part.']);
        }
    }
}

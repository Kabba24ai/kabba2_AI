<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\StoreRequest;
use App\Models\MaintenanceManagement\Part;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Map request data directly to DB columns
            $partData = [
                'part_name'        => $validated['part_name'],
                'description'      => $validated['part_description'] ?? null,
                'stock_level'      => $validated['current_stock'] ?? 0,
                'min_stock'        => $validated['min_stock'] ?? 0,
                'dni'              => $validated['dni'] ?? false,
                'general_supply_item'              => $validated['gsi'] ?? false,
                'is_active'        => true,

                // Primary part details

                'primary_part_cost'        => $validated['unit_cost'],
                'primary_part_supplier_id'         => $validated['supplier'],
                'primary_part_number'     => $validated['part_number'],

                // Alt 1
                'alt_1_part_number' => $validated['part_number_alt_1'] ?? null,
                'alt_1_part_cost'        => $validated['cost_alt_1'] ?? 0,
                'alt_1_part_supplier_id'    => $validated['supplier_alt_1'] ?? null,

                // Alt 2
                'alt_2_part_number' => $validated['part_number_alt_2'] ?? null,
                'alt_2_part_cost'        => $validated['cost_alt_2'] ?? 0,
                'alt_2_part_supplier_id'    => $validated['supplier_alt_2'] ?? null,
            ];

            // Save to DB
            $part = Part::create($partData);

            DB::commit();

            return redirect()
                ->route('admin.maintenance-management.parts.index')
                ->with('success', 'Part created successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Something went wrong while creating the part.']);
        }
    }
}

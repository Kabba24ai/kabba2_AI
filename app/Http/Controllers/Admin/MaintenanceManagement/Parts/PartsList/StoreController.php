<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\PartsList\StoreRequest;

use Illuminate\Http\Request;

use App\Models\MaintenanceManagement\PartsList;
use Throwable;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {

        $validated = $request->validated(); // get validated data

        // dd($validated);

        DB::beginTransaction();

        try {
            // Create new template
            $list = PartsList::create([
                'name'              => $validated['part_name'],
                'category_id'       => $validated['category_id'] ?? null,
                'description'       => $validated['description'] ?? null,
                'is_active'         => true,
                'created_by'        => Auth::id(),
                'selected_products' => $validated['selected_products'] ?? [],
            ]);

            // Optional: attach in pivot table
            if (!empty($validated['template_all_part_ids'])) {
                $sortOrder = 1;
                foreach ($validated['template_all_part_ids'] as $partId) {
                    $list->parts()->attach($partId, ['sort_order' => $sortOrder++]);
                }
            }

            DB::commit();

            session()->flash('active_tab', 'template');

            return redirect()
                ->route('admin.maintenance-management.parts.index')
                ->with('success', 'Template created successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Something went wrong while creating the list.']);
        }
    }
}

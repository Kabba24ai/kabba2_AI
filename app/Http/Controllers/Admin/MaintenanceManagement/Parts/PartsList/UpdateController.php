<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\PartsList\StoreRequest;
use App\Models\MaintenanceManagement\PartsList;
use Throwable;

class UpdateController extends Controller
{
    /**
     * Handle the update of an existing part template.
     */
    public function __invoke(StoreRequest $request, $unique_id)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            //  Find the existing template
            $list = PartsList::where('unique_id', $unique_id)->first();
            // PartsList::findOrFail($id);

            //  Update core template fields
            $list->update([
                'name'              => $validated['part_name'],
                'category_id'       => $validated['category_id'] ?? null,
                'description'       => $validated['description'] ?? null,
                'selected_products' => $validated['selected_products'] ?? [],
                'updated_by'        => Auth::id(),
            ]);

            //  Update associated parts (pivot table)
            if (!empty($validated['template_all_part_ids'])) {
                $syncData = [];
                $sortOrder = 1;
                foreach ($validated['template_all_part_ids'] as $partId) {
                    $syncData[$partId] = ['sort_order' => $sortOrder++];
                }
                $list->parts()->sync($syncData);
            } else {
                // If none selected, remove all related parts
                $list->parts()->detach();
            }

            DB::commit();

            session()->flash('active_tab', 'template');

            return redirect()
                ->route('admin.maintenance-management.parts.index')
                ->with('success', 'Template updated successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Something went wrong while updating the list.']);
        }
    }
}

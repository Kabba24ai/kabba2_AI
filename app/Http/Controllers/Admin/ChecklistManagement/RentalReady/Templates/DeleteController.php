<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;

class DeleteController extends Controller
{
    public function __invoke($unique_id)
    {
        DB::beginTransaction();

        try {
            // Find Template by unique_id
            $template = RentalReadyChecklistTemplate::where('unique_id', $unique_id)->firstOrFail();

            // Delete related template questions first (if no cascade)
            $template->questions()->delete();

            // Delete the template itself
            $template->delete();

            DB::commit();

            flash('Template deleted successfully.')->success();

            session()->flash('active_tab', 'templates');

            return redirect()
                ->route('admin.checklist-management.rental-ready.index')
                ->with('success', 'Template deleted successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while deleting the template.')->error();

            return redirect()
                ->back()
                ->withErrors(['error' => 'An error occurred while deleting the template.']);
        }
    }
}

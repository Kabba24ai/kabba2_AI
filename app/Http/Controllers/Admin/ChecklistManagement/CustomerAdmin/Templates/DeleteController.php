<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Services\ChecklistManagement\TemplateCrudService;

class DeleteController extends Controller
{
    public function __construct(private TemplateCrudService $templateCrudService)
    {
    }

    public function __invoke($unique_id)
    {
        try {
            $this->templateCrudService->delete(CustomerAdminTemplate::class, $unique_id);

            flash('Template deleted successfully.')->success();

            session()->flash('active_tab', 'templates');

            return redirect()
                ->route('admin.checklist-management.customer-admin.index');

        } catch (\Throwable $e) {
            report($e);

            flash('Something went wrong while deleting the template.')->error();

            return redirect()
                ->back()
                ->withErrors(['error' => 'An error occurred while deleting the template.']);
        }
    }
}

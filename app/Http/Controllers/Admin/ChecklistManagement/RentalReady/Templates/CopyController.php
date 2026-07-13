<?php
namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates;

use App\Http\Controllers\Controller;

use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
use App\Services\ChecklistManagement\TemplateCrudService;

class CopyController extends Controller
{
    public function __construct(private TemplateCrudService $templateCrudService)
    {
    }

    /**
     * Handle the incoming request.
     */
     public function __invoke( $unique_id)
    {
        try {
            $new = $this->templateCrudService->copy(
                RentalReadyChecklistTemplate::class,
                RentalReadyChecklistTemplateQuestion::class,
                $unique_id,
                'TQS',
                fn ($templateQuestion) => [
                    'question_id'  => $templateQuestion->question_id,
                    'index_number' => $templateQuestion->index_number,
                ]
            );

            //  Flash new template ID
            session()->flash('active_tab', 'templates');
            session()->flash('open_edit_template', $new->unique_id);

            flash('Template copied successfully.')->success();

            return redirect()->route('admin.checklist-management.rental-ready.index');

        } catch (\Throwable $e) {
            report($e);

            session()->flash('active_tab', 'templates');
            flash('Something went wrong while copying the template.')->error();

            return redirect()->back();
        }
    }
}

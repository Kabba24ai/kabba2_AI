<?php
namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates;

use App\Http\Controllers\Controller;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion;
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
            // NOTE: preserves the original code's exact behavior of regenerating
            // the copy's unique_id with the 'TQS' prefix (Rental Ready's own
            // creation-time prefix), not CustomerAdminTemplate's own boot()-time
            // prefix ('CATQS') — a pre-existing inconsistency between Store-created
            // and Copy-created Customer Admin templates' unique_id shape, called out
            // in PR-B4_3_TEMPLATE_REFACTOR.md and deliberately not "fixed" here.
            $new = $this->templateCrudService->copy(
                CustomerAdminTemplate::class,
                CustomerAdminTemplateQuestion::class,
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

            return redirect()->route('admin.checklist-management.customer-admin.index');

        } catch (\Throwable $e) {
            report($e);

            session()->flash('active_tab', 'templates');
            flash('Something went wrong while copying the template.')->error();

            return redirect()->back();
        }
    }
}

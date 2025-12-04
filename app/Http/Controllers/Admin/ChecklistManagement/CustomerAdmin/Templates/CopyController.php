<?php
namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Helpers\ModelHelper;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;


use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion;
// Request
use App\Http\Requests\Admin\ChecklistManagement\CustomerAdmin\Templates\StoreRequest;

class CopyController extends Controller
{
    /**
     * Handle the incoming request.
     */
     public function __invoke( $unique_id)
    {
        DB::beginTransaction();

    try {
        $template = CustomerAdminTemplate::where('unique_id', $unique_id)->firstOrFail();

        $new = $template->replicate();
        $new->template_name = $template->template_name . ' (Copy)';
        $new->unique_id = ModelHelper::generateUniqueID(new CustomerAdminTemplate, 'TQS');
        $new->save();

        foreach ($template->templateQuestions as $q) {
            CustomerAdminTemplateQuestion::create([
                'template_id' => $new->id,
                'question_id' => $q->question_id,
                'index_number' => $q->index_number,
            ]);
        }

        DB::commit();

        //  Flash new template ID
        session()->flash('active_tab', 'templates');
        session()->flash('open_edit_template', $new->unique_id);

        flash('Template copied successfully.')->success();

        return redirect()->route('admin.checklist-management.customer-admin.index');

    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);

        session()->flash('active_tab', 'templates');
        flash('Something went wrong while copying the template.')->error();

        return redirect()->back();
    }
    }
}

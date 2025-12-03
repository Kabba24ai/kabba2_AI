<?php
namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Helpers\ModelHelper;

use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
// Request
use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Templates\StoreRequest;

use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Templates\UpdateRequest;

class CopyController extends Controller
{
    /**
     * Handle the incoming request.
     */
     public function __invoke( $unique_id)
    {
        DB::beginTransaction();

    try {
        $template = RentalReadyChecklistTemplate::where('unique_id', $unique_id)->firstOrFail();

        $new = $template->replicate();
        $new->template_name = $template->template_name . ' (Copy)';
        $new->unique_id = ModelHelper::generateUniqueID(new RentalReadyChecklistTemplate, 'TQS');
        $new->save();

        foreach ($template->templateQuestions as $q) {
            RentalReadyChecklistTemplateQuestion::create([
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

        return redirect()->route('admin.checklist-management.rental-ready.index');

    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);

        session()->flash('active_tab', 'templates');
        flash('Something went wrong while copying the template.')->error();

        return redirect()->back();
    }
    }
}

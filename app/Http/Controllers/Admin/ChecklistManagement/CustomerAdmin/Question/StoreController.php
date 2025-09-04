<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Question;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Http\Requests\Admin\ChecklistManagement\CustomerAdmin\Question\StoreRequest;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
{
    $validated = $request->validated();
    
    DB::beginTransaction();

    try {
        // Create the Question
        $question = CustomerAdminQuestion::create([
        'question_name'           => $validated['question_name'],
        'category_id'             => $validated['category_id'],
        'question_delivery_text'  => $validated['question_delivery_text'],
        'question_return_text'    => $validated['question_return_text'],
        'required_question'       => $validated['required_question'] ?? 0,
    ]);

if (!$question || !$question->id) {
    throw new \Exception('Failed to create question.');
}


        // options is already an array, no need to json_decode
        $options = $validated['options'];

        foreach ($validated['options'] as $index => $option) {
        $answer = CustomerAdminQuestionAnswer::create([
        'question_id'          => $question->id,
                    'index_number'         => $index + 1,
                    'answer_delivery_text' => $option['answer_delivery_text'] ?? null,
                    'answer_return_text'   => $option['answer_return_text'] ?? null,
                    'delivery_amt'         => $option['delivery_amt'] ?? null,
                    'return_amt'           => $option['return_amt'] ?? null,
                    'required'             => $option['syncEnabled'] ?? 0,
    ]);

    if (!$answer || !$answer->id) {
        throw new \Exception("Failed to create answer at index $index");
    }
}


        DB::commit();

        flash('Question created successfully.')->success();

        session()->flash('active_tab', 'questions');
        session()->flash('active_subtab', 'questions');

        return redirect()->route('admin.checklist-management.customer-admin.index');

    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);
        dd($e->getMessage(), $e->getTraceAsString());

        flash('Something went wrong while creating the question.')->error();

        return 
        redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'An error occurred while creating the question.']);
            
    }
}
    
}

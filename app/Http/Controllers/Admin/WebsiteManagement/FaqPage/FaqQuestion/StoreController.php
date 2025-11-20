<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\FaqPage\FaqQuestion;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

// Request
use App\Http\Requests\Admin\WebsiteManagement\FaqPage\FaqQuestion\StoreRequest;
use App\Models\WebsiteManagement\FaqPage\FaqCategory;
use App\Models\WebsiteManagement\FaqPage\FaqQuestions;

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
            // Save FaqQuestions
            $FaqQuestions = FaqQuestions::create([
                'category_id'   => $validated['category'],
                'question_name'   => $validated['question'],
                'answer' => $validated['answer'] ?? null,
                'status' => $validated['active'],
                'related_question_id' => $validated['relatedCategory'],
            ]);

            DB::commit();

            flash('Faq question created successfully.')->success();

            session()->flash('active_tab', 'faq');

                return redirect()
                    ->route('admin.website-management.faq-page.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            session()->flash('active_tab', 'faq');

            flash('Something went wrong while creating the faq question.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the faq question.']);
        }
    }
}

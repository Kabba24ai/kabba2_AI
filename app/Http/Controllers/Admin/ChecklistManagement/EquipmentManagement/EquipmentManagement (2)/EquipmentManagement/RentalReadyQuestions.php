<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;



class RentalReadyQuestions extends Controller
{
    public function __invoke(Request $request)
    {

        // Load categories with questions and their answers
        $rentalreadycategories = RentalReadyChecklistCategory::with(['questions.answers'])->get();

        $questions = $rentalreadycategories->map(function ($category) {
            return [
                'title' => $category->category_name,
                'key' => strtolower(str_replace(' ', '_', $category->category_name)), // create a JS-friendly key
                'items' => $category->questions->map(function ($question) {
                    return [
                        'id' => $question->unique_id,
                        'title' => $question->question_name,
                        'required' => $question->required_question,
                        'options' => $question->answers->map(function ($answer) {
                            return [
                                'label' => $answer->answer_name,
                                'status' => $answer->type
                            ];
                        })->toArray()
                    ];
                })->toArray()
            ];
        });

        return response()->json([
            'success' => true,
            'questions' => $questions
        ]);
    }
}

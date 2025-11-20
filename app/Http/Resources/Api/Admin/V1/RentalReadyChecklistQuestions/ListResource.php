<?php

namespace App\Http\Resources\Api\Admin\V1\RentalReadyChecklistQuestions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\Admin\V1\RentalReadyChecklistCategories\ListResource as RentalReadyChecklistCategoriesListResource;
use App\Http\Resources\Api\Admin\V1\RentalReadyChecklistQuestionAnswers\ListResource as RentalReadyChecklistQuestionAnswersListResource;


class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request)
    {
        // Handle both array and object cases
        if (is_array($this->resource)) {
            $return = [
                'id' => $this['main_id'] ?? 0,
                'unique_id' => $this['id'] ?? '',
                'category_id' => $this['category_id'] ?? '',
                'question_name' => $this['question_name'] ?? '',
                'required_question' => $this['required_question'] ?? false,
                'answers' => RentalReadyChecklistQuestionAnswersListResource::collection($this['answers'] ?? []),
                'selected_answer' => new RentalReadyChecklistQuestionAnswersListResource($this['selected_answer'] ?? null),
                'note' => $this['note'] ?? '',
            ];
        } else {
            $return = [
                'id' => $this->id ?? 0,
                'unique_id' => $this->unique_id ?? '',
                'category_id' => $this->category_id ?? '',
                'question_name' => $this->question_name ?? '',
                'required_question' => $this->required_question ?? false,
                'answers' => RentalReadyChecklistQuestionAnswersListResource::collection($this->whenLoaded('answers')),
                'selected_answer' => null,
                'note' => '',
            ];
        }

        return $return;
    }
}

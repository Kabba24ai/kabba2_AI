<?php

namespace App\Http\Resources\Api\Admin\V1\CustomerChecklistQuestions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\Api\Admin\V1\CustomerChecklistCategories\ListResource as CustomerChecklistCategoriesListResource;
use App\Http\Resources\Api\Admin\V1\CustomerChecklistQuestionAnswers\ListResource as CustomerChecklistQuestionAnswersListResource;

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
        $return = [
            'id' => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? '',
            'question_name' => $this->question_name ?? '',
            'category_id' => $this->category_id ?? 0,
            'question_delivery_text' => $this->question_delivery_text ?? '',
            'question_return_text' => $this->question_return_text ?? '',
            'required_question' => $this->required_question ?? false,
            'category' => new CustomerChecklistCategoriesListResource($this->whenLoaded('category')),
            'answers' => CustomerChecklistQuestionAnswersListResource::collection($this->whenLoaded('answers')),
        ];

        return $return;
    }
}

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

        if ($this->delivery_question) {
            $deliveryText = $this->delivery_question ?? '';
        }else{
            $deliveryText = $this->question_delivery_text ?? '';
        }

        if ($this->return_question) {
            $returnText = $this->return_question ?? '';
        }else{
            $returnText = $this->question_return_text ?? '';
        }

        if ($this->question_category_id) {
            $categoryId = $this->question_category_id ?? '';
        }else{
            $categoryId = $this->category_id ?? '';
        }


        $return = [
            'id' => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? '',
            'question_name' => $this->question_name ?? '',
            'category_id' => $categoryId ?? 0,
            'question_delivery_text' => $deliveryText ?? '',
            'question_return_text' => $returnText ?? '',
            'required_question' => $this->required_question ?? true,
            'category' => new CustomerChecklistCategoriesListResource($this->whenLoaded('category')),
            'answers' => CustomerChecklistQuestionAnswersListResource::collection($this->whenLoaded('answers')),
            //  deliverAnswer is selected answer
            'deliverAnswer' => new CustomerChecklistQuestionAnswersListResource($this->whenLoaded('deliverySelectedAnswer')),
            // returnAnswer is selected answer
            'returnAnswer' => new CustomerChecklistQuestionAnswersListResource($this->whenLoaded('returnSelectedAnswer')),
        ];

        return $return;
    }
}

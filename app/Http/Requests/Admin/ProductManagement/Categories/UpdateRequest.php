<?php

namespace App\Http\Requests\Admin\ProductManagement\Categories;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->all(),['content']));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $uniqueId = $this->route('unique_id'); // assuming your route uses {unique_id}
        $parentId = $this->input('parent_id') ?? 'NULL';

        $requestArr = [
            'parent_id'=>['nullable'],
            'title' => [
            'required',
            'max:240',
                    Rule::unique('product_categories', 'title')
                        ->ignore($uniqueId, 'unique_id')
                        ->where(function ($query) use ($parentId) {
                        if ($parentId === 'NULL') {
                            $query->whereNull('parent_id');
                        } else {
                            $query->where('parent_id', $parentId);
                        }
                    }),
            ],
            'short_content' => ['max:1000'],
            'content'=>['nullable'],
            'media' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'image', 'mimes:jpeg,jpg,png', 'max:2048'
            ],
            'status' => ['required', 'in:Published,Draft,Pending'],
            'is_featured' => ['nullable', 'in:Yes,No'],
            'seo_title' => ['nullable', 'string', 'max:240'],
            'seo_description' => ['nullable', 'string', 'max:240'],
        ];

        if (!is_null($this->request->get('parent_id')) && $this->request->get('parent_id') > 0) {
            $requestArr['parent_id'] = ['required', 'exists:product_categories,id'];
        }

        return $requestArr;
    }

}

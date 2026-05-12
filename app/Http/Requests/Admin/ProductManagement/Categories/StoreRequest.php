<?php

namespace App\Http\Requests\Admin\ProductManagement\Categories;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:240'],
            'parent_id' => ['nullable', 'exists:product_categories,id'],
            'short_content' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'media' => [
                'nullable',
                'image', 'mimes:jpeg,jpg,png,webp', 'max:2048',
                'dimensions:width=650,height=650',
            ],
            'hover_media' => [
                'nullable',
                'image', 'mimes:jpeg,jpg,png,webp', 'max:2048',
                'dimensions:width=650,height=650',
            ],
            'status' => ['required', 'in:Published,Draft,Pending'],
            'schedule_assignment_category_id' => ['nullable', 'exists:product_categories,id'],
            'is_featured' => ['nullable', 'in:Yes,No'],
            'seo_title' => ['nullable', 'string', 'max:240'],
            'seo_description' => ['nullable', 'string', 'max:240'],

            'products' => ['nullable', 'array'],
            'products.*' => [
                function ($attribute, $value, $fail) {
                    $hasProduct = !empty($value['product_id']);
                    $hasSubcategory = !empty($value['sub_category_id']);

                    if ($hasProduct === $hasSubcategory) {
                        $fail('Each products row must contain either product_id or sub_category_id.');
                    }
                },
            ],
            'products.*.product_id' => ['nullable', 'exists:products,id'],
            'products.*.sub_category_id' => ['nullable', 'exists:product_categories,id'],
            'products.*.sort_order' => ['required', 'integer', 'min:1'],

            'subcategories' => ['nullable', 'array'],
            'subcategories.*' => ['exists:product_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'media.required' => 'Please upload a media file.',
        ];
    }
}

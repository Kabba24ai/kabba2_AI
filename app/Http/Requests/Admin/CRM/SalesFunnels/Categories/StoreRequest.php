<?php

namespace App\Http\Requests\Admin\Crm\SalesFunnels\Categories;

use App\Http\Requests\ApiBaseFormRequest;

class StoreRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255', 'unique:sales_funnel_categories,category_name'],
            'description' => ['nullable', 'string'],
            'color_code' => ['nullable', 'string', 'max:7'],
        ];
    }
}

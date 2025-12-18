<?php

namespace App\Http\Requests\Admin\Crm\SalesFunnels\Categories;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $uniqueId = $this->route('unique_id');

        return [
            'category_name' => ['required', 'string', 'max:255', 'unique:sales_funnel_categories,category_name,' . $uniqueId . ',unique_id'],
            'description' => ['nullable', 'string'],
            'color_code' => ['nullable', 'string', 'max:7'],
        ];
    }
}

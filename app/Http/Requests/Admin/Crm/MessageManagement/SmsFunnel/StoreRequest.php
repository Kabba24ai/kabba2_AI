<?php

namespace App\Http\Requests\Admin\Crm\MessageManagement\SmsFunnel;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

   public function rules(): array
{
    return [
        'sms_funnel_cat_id'   => ['required', 'exists:sms_categories,id'],
        'name'         => ['required'],
        'description'  => ['nullable'],
    ];
}

protected function prepareForValidation(): void
{
    $this->merge(
        PurifyHelper::purify($this->only([
            'sms_funnel_cat_id',
            'name',
            'description',
        ]))
    );
}


}

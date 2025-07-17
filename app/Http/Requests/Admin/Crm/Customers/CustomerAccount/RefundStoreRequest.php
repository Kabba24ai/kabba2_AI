<?php
namespace App\Http\Requests\Admin\Crm\Customers\CustomerAccount;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;
use Illuminate\Validation\Rule;

class RefundStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->all(), ['content']));
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', Rule::in([
                'Damaged Item',
                'Wrong Item Shipped',
                'Customer Cancellation',
                'Billing Overcharge',
                'Duplicate Charge',
                'Other',
            ])],
            'responsible_person' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
           
        ];
    }
}

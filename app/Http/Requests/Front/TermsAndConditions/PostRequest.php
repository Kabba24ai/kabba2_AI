<?php

namespace App\Http\Requests\Front\TermsAndConditions;

use App\Http\Requests\ApiBaseFormRequest;
use App\Models\Orders\Order;

class PostRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        $order = $this->route('orderUniqueId') ? Order::where('unique_id', $this->route('orderUniqueId'))->first() : null;
        $customerApprovalRequired = false;
        if ($order && strpos($order->pending_terms_content, 'customer_approval[]') !== false) {
            $customerApprovalRequired = true;
        }
        return [
            'customer_approval' => $customerApprovalRequired ? 'required|array' : 'nullable|array',
            'signature' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'customer_approval.required' => 'Please provide your approvals.',
            'signature.required' => 'Signature is required.',
        ];
    }
}

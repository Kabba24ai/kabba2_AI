<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Http\Requests\Admin\OrderManagement\Orders\Concerns\VerifiesProcessedBy;
use App\Http\Requests\ApiBaseFormRequest;

class VoidRequest extends ApiBaseFormRequest
{
    use VerifiesProcessedBy;

    public function rules(): array
    {
        // Void carries no other inputs — the existing confirmation-only
        // flow is unchanged apart from the Processed By verification.
        return $this->processedByRules();
    }
}

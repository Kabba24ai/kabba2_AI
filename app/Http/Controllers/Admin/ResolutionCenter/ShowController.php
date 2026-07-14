<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Enums\Orders\OrderPaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Customers\ResolutionCase;
use App\Services\CustomerCreditService;
use App\Services\ResolutionCenterService;

class ShowController extends Controller
{
    public function __invoke(string $uniqueId)
    {
        $case = ResolutionCase::with(['customer', 'order', 'responsiblePerson', 'managerOverrideUser', 'creditIssued', 'assignedTo', 'store', 'activityLogs.user'])
            ->where('unique_id', $uniqueId)
            ->firstOrFail();

        // Live figures for display — never read from the case's own
        // snapshot columns, which are historical record only.
        $currentBalance = (float) $case->order->balance_due;
        $currentStoreCredit = CustomerCreditService::remainingBalance($case->customer_id);
        $customerStatus = ResolutionCenterService::customerStatus($case->customer_id);

        return view('admin.resolution_center.show', [
            'case' => $case,
            'currentBalance' => $currentBalance,
            'currentStoreCredit' => $currentStoreCredit,
            'customerStatus' => $customerStatus,
            'paymentMethodOptions' => OrderPaymentMethod::canonical(),
        ]);
    }
}

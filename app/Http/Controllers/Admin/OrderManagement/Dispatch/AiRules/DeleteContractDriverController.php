<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;

/**
 * Deactivate a contract driver (status → Inactive) rather than hard-delete: the row
 * may be referenced by existing delivery_by / pickup_by assignments, and
 * deactivating removes it from all active driver lists without destroying history.
 */
class DeleteContractDriverController extends Controller
{
    public function __invoke(int $id)
    {
        $user = User::where('id', $id)->where('is_contract_driver', true)->firstOrFail();
        $user->update(['status' => 'Inactive']);

        return redirect()
            ->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'contract_drivers'])
            ->with('success', 'Contract driver deactivated.');
    }
}

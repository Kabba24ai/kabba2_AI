<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Helpers\CustomHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteController extends Controller
{
   public function __invoke($id): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $transaction = CustomerAccount::findOrFail($id);

            // Reverse transaction effect
            CustomHelper::reverseTransactionEffect($transaction);

            $transaction->delete();

            DB::commit();

            flash('Transaction successfully deleted.')->success();
            // session()->flash('active_tab', 'credit');
session(['active_tab' => 'credit']);
            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollBack();
            session(['active_tab' => 'credit']);
            Log::error($e);
            flash('Failed to delete transaction.')->error();
            return redirect()->back()->withErrors(['error' => 'Delete failed. Please try again.']);
        }
    }

}

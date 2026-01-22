<?php
namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\Customers\CustomerAccount\RefundStoreRequest;
use App\Models\Customers\CustomerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Iam\Personnel\User ;
use App\Helpers\CustomHelper;

class RefundStoreController extends Controller
{
    /**
     * Handle the incoming refund request.
     */
    public function __invoke(RefundStoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $record = new CustomerAccount();
            $record->customer_id = $validated['customer_id'];
            $record->amount = $validated['amount'];
            $record->reason = $validated['reason'];
            // $record->responsible_person = $validated['responsible_person'];

            // Fetch user and store both ID and full_name
                $user = User::findOrFail($validated['responsible_person']);
                $record->responsible_person_id = $user->id ?? '';
                $record->responsible_person_name = $user->full_name ?? '';

            $record->notes = $validated['notes'] ?? null;
            $record->date = now();
            $record->type = 'refund';

            $record->save();

            CustomHelper::updateCreditBalance($record);

            DB::commit();

            flash('Refund processed successfully.')->success();
            // session()->flash('active_tab', 'credit');
            session(['active_tab' => 'credit']);

            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while processing the refund.')->error();
            // session()->flash('active_tab', 'credit');
            session(['active_tab' => 'credit']);

            Log::error('Refund error: '.$e->getMessage());

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred while processing the refund.',
            ]);
        }
    }
}

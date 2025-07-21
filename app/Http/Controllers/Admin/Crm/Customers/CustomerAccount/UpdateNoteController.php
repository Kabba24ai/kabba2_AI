<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Http\Controllers\Controller;

use App\Models\Customers\CustomerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Iam\Personnel\User ;
use App\Models\Configurations\Setting;
use App\Helpers\CustomHelper;

class UpdateNoteController extends Controller
{
    public function __invoke(Request $request)
    {
        $transaction = CustomerAccount::where('unique_id', $request->id)->firstOrFail();
        $transaction->notes = $request->note;
        $transaction->save();

        return response()->json([
            'success' => true,
            'message' => 'Note updated successfully.',
        ]);
    }
}

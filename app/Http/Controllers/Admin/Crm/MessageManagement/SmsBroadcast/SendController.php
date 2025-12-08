<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcast;
use Illuminate\Http\Request;
use Exception;

class SendController extends Controller
{
    public function __invoke($id)
    {
        try {

            $broadcast = SmsBroadcast::findOrFail($id);

            $broadcast->update([
                'status' => 'pending',
                'send_date' => now(),
            ]);

            return redirect()->back()->with('success', 'SMS Broadcast moved to Pending!');

        } catch (Exception $e) {

            return redirect()->back()->with('error', 'Failed to update SMS Broadcast: ' . $e->getMessage());
        }
    }
}

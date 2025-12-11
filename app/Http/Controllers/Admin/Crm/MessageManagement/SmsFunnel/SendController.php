<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsFunnel;
use Illuminate\Http\Request;
use Exception;

class SendController extends Controller
{
    public function __invoke($id)
    {
        try {

            $broadcast = SmsFunnel::findOrFail($id);

            $broadcast->update([
                'status' => 'pending'
            ]);

                // Store in session to auto-open 
        session()->flash('active_sub_tab', 'smsfunnel');


            return redirect()->back()->with('success', 'SMS Funnel moved to Pending!');

        } catch (Exception $e) {

            return redirect()->back()->with('error', 'Failed to update SMS Funnel: ' . $e->getMessage());
        }
    }
}

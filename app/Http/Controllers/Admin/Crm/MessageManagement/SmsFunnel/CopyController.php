<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsFunnel;

class CopyController extends Controller
{
    public function __invoke($id)
    {
        $funnel = SmsFunnel::findOrFail($id);

        $newFunnel = $funnel->replicate();
        $newFunnel->name = $funnel->name . " (Copy)";
        $newFunnel->save();

                 // Store in session to auto-open 
        session()->flash('active_sub_tab', 'smsfunnel');

        
        // Store in session to auto-open edit modal
        session()->flash('open_edit_sms_funnel', $newFunnel->id);

       

        return redirect()->back()->with('success', 'SMS Funnel copied successfully!');
    }
}

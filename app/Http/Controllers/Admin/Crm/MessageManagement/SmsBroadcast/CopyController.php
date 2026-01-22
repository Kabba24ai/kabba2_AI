<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcast;
use Illuminate\Http\RedirectResponse;

class CopyController extends Controller
{
    public function __invoke($id): RedirectResponse
    {
        $broadcast = SmsBroadcast::findOrFail($id);

        // Duplicate the broadcast
        $newBroadcast = $broadcast->replicate(); // copies all fields
        $newBroadcast->name = $broadcast->name . ' (Copy)';
        $newBroadcast->save();

        // Store in session to auto-open edit modal
        session()->flash('open_edit_template', $newBroadcast->id);

        // Redirect back to the same page
        return redirect()->back();
    }
}

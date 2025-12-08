<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\SmsBroadcast;
use App\Models\Customers\SmsCategory;



class IndexController extends Controller
{

    public function __invoke(Request $request)
{
    // 1. Created broadcasts (not sent)
    $createdBroadcasts = SmsBroadcast::with('category')
        ->where('status', 'created')
        ->latest()
        ->get();

    // 2. Pending broadcasts (for main table)
    $pendingBroadcasts = SmsBroadcast::with('category')
        ->where('status', 'pending')
        ->latest()
        ->get();

    // 3. Optional: all broadcasts
    $smsBroadcasts = SmsBroadcast::with('category')->latest()->get();


        $SmsCategory = SmsCategory::orderBy('created_at', 'DESC')->get();



    return view('admin.crm.message_management.index', [
        'createdBroadcasts' => $createdBroadcasts,
        'pendingBroadcasts' => $pendingBroadcasts,
        'smsBroadcasts'     => $smsBroadcasts,
        'SmsCategory'     => $SmsCategory,

    ]);
}

}

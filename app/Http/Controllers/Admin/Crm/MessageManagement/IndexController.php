<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\SmsBroadcast;
use App\Models\Customers\SmsCategory;
use App\Models\Customers\SmsFunnel;


class IndexController extends Controller
{

    public function __invoke(Request $request)
{
    //  Created broadcasts (not sent)
    $createdBroadcasts = SmsBroadcast::with('category')
        ->where('status', 'created')
        ->orderBy('name', 'ASC')
        ->get();

    //  Pending broadcasts (for main table)
    $pendingBroadcasts = SmsBroadcast::with('category')
        ->where('status', 'pending')
        ->orderBy('name', 'ASC')
        ->get();

    //  Optional: all broadcasts
    $smsBroadcasts = SmsBroadcast::with('category')->orderBy('name', 'ASC')->get();

    $SmsCategory = SmsCategory::orderBy('name', 'ASC')->get();

    // NEW: Created Funnels
    $createdFunnels = SmsFunnel::with('category')
        ->where('status', 'created')
        ->orderBy('name', 'ASC')
        ->get();

         //  Pending broadcasts (for main table)

        
    $smsfunnels = SmsFunnel::with('category')->orderBy('name', 'ASC')->get();

    return view('admin.crm.message_management.index', [
        'createdBroadcasts' => $createdBroadcasts,
        'pendingBroadcasts' => $pendingBroadcasts,
        'smsBroadcasts'     => $smsBroadcasts,
        'SmsCategory'     => $SmsCategory,
        'createdFunnels'    => $createdFunnels ,
        'smsfunnels'    => $smsfunnels ,
    ]);
}

}

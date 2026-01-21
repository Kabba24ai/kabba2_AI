<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsCreatedBroadcast;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\SmsBroadcast;
use App\Models\Customers\SmsFunnel;
use Illuminate\Support\Facades\DB;


class GetSelectController extends Controller
{
    public function __invoke(Request $request)
    {
       $broadcasts = SmsBroadcast::with('category')
        ->where('status', 'created')
        ->get()
        ->map(function ($b) {
            return [
                'id' => $b->id,
                'name' => $b->name,
                'description' => $b->description,
                'category' => $b->category->name ?? 'No Category',
                'type' => 'broadcast',
            ];
        });

    $funnels = SmsFunnel::with('category')
        ->where('status', 'created')
        ->get()
        ->map(function ($f) {
            return [
                'id' => $f->id,
                'name' => $f->name,
                'description' => $f->description,
                'category' => $f->category->name ?? 'No Category',
                'type' => 'funnel',
            ];
        });

    return response()->json(
        $broadcasts->merge($funnels)->values()
    );
    }
}

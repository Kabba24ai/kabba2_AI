<?php

namespace App\Http\Controllers\Front\Stores;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    public function __invoke(Request $request, string $unique_id)
    {
        $store = Store::where('unique_id', $unique_id)
            ->active()
            ->with(['state', 'hoursOfOperation'])
            ->firstOrFail();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $hoursMap = $store->hoursOfOperation->keyBy('day_name');

        return view('front.stores.show', [
            'title' => $store->store_name,
            'store' => $store,
            'days'  => $days,
            'hoursMap' => $hoursMap,
        ]);
    }
}

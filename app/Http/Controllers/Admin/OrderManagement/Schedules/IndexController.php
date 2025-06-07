<?php

namespace App\Http\Controllers\Admin\OrderManagement\Schedules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request)
    {
        // Sample schedule data (mocked)
        $items = Collection::make([
            [
                'id' => 4650,
                'product_name' => 'Chipper 12" - W/E Special',
                'customer_name' => 'Crescencio A-COSTA',
                'delivery_address' => '624 N Woodson Rd, Clarksville, TN, 37043',
                'phone' => '(501) 366-6454',
                'equip_id' => 'Pending',
                'equip_name' => '',
                'delivery_date' => 'May 02',
                'delivery_time' => '02:00 PM',
                'return_date' => 'May 05',
                'return_time' => '09:00 AM',
                'delivery_mode' => 'truck',
                'return_mode' => 'store',
                'delivery_status' => 'pending',
                'return_status' => 'pending',
                'payment_status' => 'pending',
            ],
            [
                'id' => 4706,
                'product_name' => 'Chipper 12" - W/E Special',
                'customer_name' => 'Crescencio A-COSTA',
                'delivery_address' => '8749 South Tatum Creek Rd, Lyles, TN, 37098',
                'phone' => '(615) 202-4555',
                'equip_id' => 'Pending',
                'equip_name' => '',
                'delivery_date' => 'May 02',
                'delivery_time' => '02:00 PM',
                'return_date' => 'May 05',
                'return_time' => '09:00 AM',
                'delivery_mode' => 'truck',
                'return_mode' => 'store',
                'delivery_status' => 'pending',
                'return_status' => 'pending',
                'payment_status' => 'pending',
            ],
            [
                'id' => 4838,
                'product_name' => '11 Hp Stud Steer - Weekly',
                'customer_name' => 'Jerry Verner',
                'delivery_address' => '1908 Grand Ave, Nashville, TN, 37212',
                'phone' => '(607) 951-4154',
                'equip_id' => 'TAK-SS-5',
                'equip_name' => 'Takeuchi TL12',
                'delivery_date' => 'May 02',
                'delivery_time' => '02:00 PM',
                'return_date' => 'May 05',
                'return_time' => '09:00 AM',
                'delivery_mode' => 'truck',
                'return_mode' => 'store',
                'delivery_status' => 'pending',
                'return_status' => 'pending',
                'payment_status' => 'pending',
            ],
            [
                'id' => 4925,
                'product_name' => '3 Ton – Weekly',
                'customer_name' => 'Raj Chotaliya',
                'delivery_address' => '1600 Iron Hill Rd, Dickson, TN, 37055',
                'phone' => '(931) 279-4769',
                'equip_id' => 'Pending',
                'equip_name' => '',
                'delivery_date' => 'May 02',
                'delivery_time' => '02:00 PM',
                'return_date' => 'May 05',
                'return_time' => '09:00 AM',
                'delivery_mode' => 'truck',
                'return_mode' => 'store',
                'delivery_status' => 'pending',
                'return_status' => 'pending',
                'payment_status' => 'pending',
            ],
            [
                'id' => 5008,
                'product_name' => '9 Ton w/Cab - Weekly',
                'customer_name' => 'Gunner Bradford',
                'delivery_address' => '173 Arnhes Dr, Nashville, TN, 37210',
                'phone' => '(615) 538-7822',
                'equip_id' => 'CAS-ME-2',
                'equip_name' => 'Case CX57',
                'delivery_date' => 'May 02',
                'delivery_time' => '02:00 PM',
                'return_date' => 'May 05',
                'return_time' => '09:00 AM',
                'delivery_mode' => 'truck',
                'return_mode' => 'store',
                'delivery_status' => 'pending',
                'return_status' => 'pending',
                'payment_status' => 'pending',
            ],
        ]);

        // Pagination setup
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        $currentItems = $items->slice($offset, $perPage)->map(fn($item) => (object) $item)->values();

        $schedules = new LengthAwarePaginator($currentItems, $items->count(), $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('admin.order_management.schedules.index', compact('schedules'));
    }
}

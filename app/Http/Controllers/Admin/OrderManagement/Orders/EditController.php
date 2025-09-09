<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;

// Helpers
use App\Helpers\ConfigurationHelper;

// Models
use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;
use App\Models\Orders\Order;
use App\Models\Stores\Store;


class EditController extends Controller
{
  
    public function __invoke($uniqueid)
    {
        $order = Order::with(['products.product', 'billingAddress', 'shippingAddress', 'notes', 'media.media',
            'products.checklistQuestions.answers',
            'products.checklistQuestions.deliverySelectedAnswer',
            'products.checklistQuestions.returnSelectedAnswer',
            'products.checklistQuestions.latestAnswer',
            'products.checklistQuestions.latestValidAnswer'])->where('unique_id', $uniqueid)->firstOrFail();

        $stores = Store::orderBy('store_name')->get();
        $employees = User::orderBy('first_name')->get();
        $states = State::orderBy('name')->get();
        $paymentSetting = ConfigurationHelper::getSettings('Payment Settings');


        // dd($order->products);

        return view('admin.order_management.orders.edit', compact('order', 'stores', 'employees', 'states', 'paymentSetting'));
    }
}

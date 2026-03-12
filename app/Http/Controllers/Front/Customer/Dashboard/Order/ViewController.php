<?php

namespace App\Http\Controllers\Front\Customer\Dashboard\Order;

use App\Http\Controllers\Controller;

// Helpers
use App\Helpers\ConfigurationHelper;

// Models
use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;
use App\Models\Orders\Order;
use App\Models\Stores\Store;
use App\Models\ProductManagement\ProductCategory;

class ViewController extends Controller
{
    public function __invoke($uniqueid)
    {
        $order = Order::with(['products.product', 'billingAddress', 'shippingAddress', 'notes', 'media.media', 'products.checklistQuestions.answers', 'products.checklistQuestions.deliverySelectedAnswer', 'products.checklistQuestions.returnSelectedAnswer', 'products.checklistQuestions.latestAnswer', 'products.deliverySignatureMedia', 'products.returnSignatureMedia', 'products.checklistQuestions.latestValidAnswer', 'products.equipment', 'products.softAssignment.equipment', 'products.damageChargeLogs', 'latestReceipt', 'extraCharges.responsiblePerson', 'extraCharges.customer', 'extraCharges.orderProduct', 'extraCharges.orderProduct'])
            ->where('unique_id', $uniqueid)
            ->firstOrFail();

        $stores = Store::orderBy('store_name')->get();
        $employees = User::orderBy('first_name')->get();
        $states = State::orderBy('name')->get();
        $paymentSetting = ConfigurationHelper::getSettings('Payment Settings');

        // Get categories with equipments for assignment modal
        $categories = ProductCategory::with(['equipments' => function ($query) {
            $query->orderBy('equipment_name');
        }])->orderBy('title')->get();

        //  define payments from relationship
        $payments = $order->extraCharges->sortByDesc('type');

        // dd($order);

        return view('front.customer.dashboard.order_view', compact('order', 'stores', 'employees', 'states', 'paymentSetting', 'payments', 'categories'));
    }
}

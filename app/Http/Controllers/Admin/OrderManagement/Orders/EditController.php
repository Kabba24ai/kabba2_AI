<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;

// Helpers
use App\Helpers\ConfigurationHelper;

// Models
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;
use App\Models\Orders\Order;
use App\Models\Stores\Store;
use App\Models\ProductManagement\ProductCategory;

class EditController extends Controller
{
    public function __invoke($uniqueid)
    {
        $order = Order::with(['products.product', 'billingAddress', 'shippingAddress', 'notes','payments', 'media.media', 'products.checklistQuestions.answers', 'products.checklistQuestions.deliverySelectedAnswer', 'products.checklistQuestions.returnSelectedAnswer', 'products.checklistQuestions.latestAnswer', 'products.deliverySignatureMedia', 'products.returnSignatureMedia', 'products.checklistQuestions.latestValidAnswer', 'products.equipment', 'products.softAssignment.equipment', 'products.damageChargeLogs', 'latestReceipt', 'extraCharges.responsiblePerson', 'extraCharges.customer', 'extraCharges.orderProduct', 'extraCharges.orderProduct', 'podPaymentLink'])
            ->where('unique_id', $uniqueid)
            ->firstOrFail();

        $assignedUserIds = collect()
            ->merge($order->products->pluck('delivery_by'))
            ->merge($order->products->pluck('pickup_by'))
            ->merge($order->extraCharges->pluck('responsible_person_id'))
            ->filter()
            ->unique()
            ->values();

        $stores = Store::orderBy('store_name')->get();
        $employees = User::activeOrIds($assignedUserIds)->orderBy('first_name')->get();
        $drivers = User::activeOrIds($assignedUserIds)->where('is_driver', true)->orderBy('first_name')->get();
        $states = State::orderBy('name')->get();
        $paymentSetting = ConfigurationHelper::getSettings('Payment Settings');
        $allocatedHoursSettings = ConfigurationHelper::getSettings('Allocated Hours Settings');
        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

        // Get categories with equipments for assignment modal
        $categories = ProductCategory::with(['equipments' => function ($query) {
            $query->orderBy('equipment_name');
        }])->orderBy('title')->get();

        //  define payments from relationship
        $payments = $order->extraCharges->sortByDesc('type');

        // Additional charges (fuel/damage) linked to this order via customer_accounts
        $additionalCharges = CustomerAccount::where('order_id', $order->id)
            ->where('type', 'charge')
            ->whereIn('reason', ['Fuel Charge', 'Damages'])
            ->with(['responsibleUser:id,first_name,last_name', 'orderProduct:id,unique_id'])
            ->latest('id')
            ->get();

        // Extension orders always have a suffixed order_number (e.g. #047-A, #047-B).
        // Reorders also set reference_order_number but get a new sequential number — exclude them.
        $relatedOrders = Order::where('reference_order_number', $order->order_number)
            ->where('order_number', 'like', $order->order_number . '-%')
            ->with(['lastPayment', 'payments', 'notes'])
            ->latest('id')
            ->get();

        return view('admin.order_management.orders.edit', compact('order', 'stores', 'employees', 'drivers', 'states', 'paymentSetting', 'payments', 'categories', 'allocatedHoursSettings', 'sales_tax', 'relatedOrders', 'additionalCharges'));
    }
}

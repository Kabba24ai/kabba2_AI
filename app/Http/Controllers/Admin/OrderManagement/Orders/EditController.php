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
use App\Models\Dashboard\ResolutionNotePreset;
use App\Models\Dashboard\FuelNotePreset;

// Services
use App\Services\CustomerCreditService;

class EditController extends Controller
{
    public function __invoke($uniqueid)
    {
        $order = Order::with(['products.product', 'billingAddress', 'shippingAddress', 'notes','payments', 'media.media', 'products.checklistQuestions.answers', 'products.checklistQuestions.deliverySelectedAnswer', 'products.checklistQuestions.returnSelectedAnswer', 'products.checklistQuestions.latestAnswer', 'products.deliverySignatureMedia', 'products.returnSignatureMedia', 'products.checklistQuestions.latestValidAnswer', 'products.equipment', 'products.softAssignment.equipment', 'products.damageChargeLogs', 'latestReceipt', 'extraCharges.responsiblePerson', 'extraCharges.customer', 'extraCharges.orderProduct', 'extraCharges.orderProduct', 'podPaymentLink', 'podPaymentLink.activities', 'licenseMedia', 'products.deliveryMedia', 'products.pickupMedia', 'products.deliveryEmployee', 'products.pickupEmployee', 'referenceOrder.products.equipment.productCategory', 'referenceOrder.products.softAssignment.equipment.productCategory', 'referenceOrder.products.product.categories'])
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

        // $drivers = User::activeOrIds($assignedUserIds)->where('is_driver', true)->orderBy('first_name')->get();

        $drivers = User::activeOrIds($assignedUserIds)->orderBy('first_name')->get();
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

        // Billing Engine charges for this order
        $billingCharges = \App\Models\Orders\BillingCharge::where('parent_order_id', $order->id)
            ->with(['createdBy:id,first_name,last_name', 'responsiblePerson:id,first_name,last_name', 'childOrder:id,unique_id,order_number', 'legacyCustomerAccount:id,unique_id', 'orderProduct:id'])
            ->latest()
            ->get();

        $resolutionPresets = ResolutionNotePreset::orderBy('label')->get();
        $fuelNotePresets   = FuelNotePreset::orderBy('label')->get();

        // Phase 3.2 — Order Entry Integration. Both figures sourced entirely
        // from CustomerCreditService, never computed or cached here.
        $customerCreditSummary = CustomerCreditService::summaryForCustomer($order->customer_id);
        $orderAppliedCredit = CustomerCreditService::appliedToOrder($order->id);

        // Canonical New Task modal (Add Task button) — passed to the shared
        // partial explicitly; this page's $categories are product categories.
        $taskModalData = \App\Support\Tasks\UnifiedTaskModalData::make();

        // Goodwill (FD-002 §7.3): only users who genuinely hold
        // `goodwill.apply` may be offered as the authorising manager. Read
        // from Spatie directly rather than through can()/Gate, because
        // AppServiceProvider registers Gate::before(fn () => true) — an
        // ability check there would return every active user. This list is a
        // convenience for the operator; the service re-verifies the selected
        // manager's permission server-side before writing anything.
        //
        // Guarded: Spatie's permission() scope THROWS when the permission row
        // does not exist, which would take this entire page down — not just
        // the Goodwill panel — on any environment where ModuleSeeder has not
        // run yet. An order page must not 500 because an optional feature's
        // permission is unseeded.
        $goodwillManagers = collect();

        try {
            $goodwillManagers = \App\Models\Iam\Personnel\User::where('status', 'Active')
                ->permission('goodwill.apply')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']);
        } catch (\Throwable $e) {
            report($e);
        }

        return view('admin.order_management.orders.edit', compact('order', 'stores', 'employees', 'drivers', 'states', 'paymentSetting', 'payments', 'categories', 'allocatedHoursSettings', 'sales_tax', 'relatedOrders', 'additionalCharges', 'billingCharges', 'resolutionPresets', 'fuelNotePresets', 'customerCreditSummary', 'orderAppliedCredit', 'taskModalData', 'goodwillManagers'));
    }
}

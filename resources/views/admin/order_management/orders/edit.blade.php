@extends('admin.layouts.app')

@section('title', 'Edit Order')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Order Header Section --}}
    <div id="orderHeaderCard" class="bg-white rounded-xl border border-gray-200 shadow-[0_1px_3px_rgba(16,24,40,0.1)] mb-6">

        {{-- Row 1: identity + icon action buttons --}}
        <div class="flex flex-wrap items-start justify-between gap-4 px-5 pt-4 pb-3">

            {{-- LEFT: Order + Customer/Company --}}
            <div class="min-w-0">
                <div class="text-lg font-semibold text-gray-800">
                    <span class="text-gray-700">Order ID:</span> {{ $order->order_number }}
                    @if (!empty($order->reference_order_number))
                        <span class="text-lg text-gray-600 ml-2">
                            <span class="text-gray-700">Ref. ID:</span>
                            @if ($order->referenceOrder?->unique_id)
                                <a href="{{ route('admin.order-management.orders.edit', $order->referenceOrder->unique_id) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $order->reference_order_number }}
                                </a>
                            @else
                                {{ $order->reference_order_number }}
                            @endif
                        </span>
                    @endif
                </div>

                <div class="mt-1 leading-tight">
                    <div class="text-sm font-medium text-gray-800">
                        Customer: {{ $order->customer_name }}
                    </div>
                    {{-- Company shown only when the customer actually has one. --}}
                    @if (!empty($order->company_name))
                        <div class="text-xs italic text-gray-600">
                            Company: {{ $order->company_name }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT: icon actions + section triggers. Every id/data attr/class
                 that JS binds to is preserved; only the styling changed. --}}
            <div class="flex items-center gap-1.5 flex-wrap justify-end">
                {{-- Add Task — opens the canonical New Task modal (order + customer pre-linked) --}}
                <button id="addTaskBtn" type="button" title="Add Task"
                    data-open-task-modal
                    data-order-id="{{ $order->id }}"
                    data-order-number="{{ $order->order_number }}"
                    data-customer-id="{{ $order->customer_id }}"
                    data-customer-name="{{ $order->customer?->full_name }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors shadow-sm cursor-pointer">
                    <x-heroicon-o-clipboard-document-list class="w-[18px] h-[18px]" />
                </button>
                {{-- Reorder (modal relocated to page root below) --}}
                <button id="reorderBtn" type="button" title="Reorder"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 focus:outline-none transition-colors shadow-sm">
                    <x-heroicon-o-arrow-path-rounded-square class="w-[18px] h-[18px]" />
                </button>
                {{-- Email Receipt --}}
                <a href="{{ route('admin.order-management.orders.receipt-email', $order->unique_id) }}" title="Email Receipt"
                    class="receipt-action inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors shadow-sm">
                    <x-heroicon-o-envelope class="w-[18px] h-[18px]" />
                    <svg class="hidden w-4 h-4 animate-spin loader-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                </a>
                {{-- Print Receipt --}}
                <a href="{{ route('admin.order-management.orders.receipt-download', $order->unique_id) }}"
                    target="_blank" rel="noopener noreferrer" title="Print Receipt"
                    class="receipt-action inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors shadow-sm">
                    <x-heroicon-o-printer class="w-[18px] h-[18px]" />
                    <svg class="hidden w-4 h-4 animate-spin loader-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                </a>
                {{-- Operations History --}}
                <button id="operationsHistoryBtn" type="button" title="Operations History"
                    data-ops-history-open
                    data-url="{{ route('admin.order-management.orders.operations-history', $order->unique_id) }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors shadow-sm cursor-pointer">
                    <x-heroicon-o-clock class="w-[18px] h-[18px]" />
                </button>

                <span class="h-6 w-px bg-gray-200 mx-1"></span>

                {{-- Section triggers: reveal the target section (hidden by default),
                     scroll to it, and for Fuel/Damage/Order Enhancement click the
                     existing in-section button so all add-charge logic is reused. --}}
                <button type="button" title="Fuel Charge" data-reveal="#billingEngineSection" data-click="#beFuelBtn"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-orange-500 text-white hover:bg-orange-600 transition-colors shadow-sm">
                    {{-- Standard Fuel Charge icon (fuel pump) — matches the Billing Engine button + charge rows. --}}
                    <svg fill="currentColor" class="w-[18px] h-[18px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                        <path d="M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM160 144L160 240C160 248.8 167.2 256 176 256L304 256C312.8 256 320 248.8 320 240L320 144C320 135.2 312.8 128 304 128L176 128C167.2 128 160 135.2 160 144z"/>
                    </svg>
                </button>
                <button type="button" title="Damage Charge" data-reveal="#billingEngineSection" data-click="#beDamageBtn"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors shadow-sm">
                    <x-heroicon-o-exclamation-triangle class="w-[18px] h-[18px]" />
                </button>
                <button type="button" title="Order Enhancement" data-reveal="#billingEngineSection" data-click="#beExtensionBtn"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm">
                    <x-heroicon-o-calendar class="w-[18px] h-[18px]" />
                </button>
                <button type="button" title="Store Credit" data-reveal="#storeCreditSection"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-teal-600 text-white hover:bg-teal-700 transition-colors shadow-sm">
                    <x-heroicon-o-gift class="w-[18px] h-[18px]" />
                </button>
                @can('resolution_center.use')
                <button type="button" title="Resolution Center" data-reveal="#resolutionCenterSection"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-600 text-white hover:bg-slate-700 transition-colors shadow-sm">
                    <x-heroicon-o-scale class="w-[18px] h-[18px]" />
                </button>
                @endcan

                {{-- Resend Payment Link — only when a balance is still due
                     (a COD / pay-on-delivery order that isn't settled). --}}
                @php
                    $hasCodPending = $order->payments
                        ->where('payment_method', 'COD')
                        ->where('status', 'Pending')
                        ->isNotEmpty();
                @endphp
                @if($hasCodPending && $order->balance_due > 0)
                    <span class="h-6 w-px bg-gray-200 mx-1"></span>
                    <button id="resendPodPaymentLinkBtn" type="button" title="Resend Payment Link"
                        data-url="{{ route('admin.order-management.orders.resend-pod-payment-link', $order->unique_id) }}"
                        class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg border border-teal-300 bg-teal-50 text-teal-700 text-xs font-semibold hover:bg-teal-100 transition-colors shadow-sm">
                        <x-heroicon-o-paper-airplane class="w-4 h-4 btn-icon" />
                        <span class="btn-label">Resend Payment Link</span>
                        <svg class="hidden w-4 h-4 ml-1 animate-spin text-teal-700 btn-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        {{-- Payment Status summary (canonical logic unchanged) --}}
        @php
            use App\Enums\Orders\OrderPaymentStatus;
            use App\Services\Orders\OrderPaymentSummary;
                // Payment Architecture Finalization (Tier 2): the badges
                // below used to gate on Order::last_payment_status/
                // last_payment_type — whichever payment row happened to be
                // entered last — which can disagree with the order's real
                // aggregate state on any order with more than one payment
                // row (split payment, a retried failed attempt, etc.).
                // Sourced from the canonical OrderPaymentSummary instead.
                //
                // Status precedence (see OrderPaymentSummary::
                // unresolvedPaymentAttempts docblock for the full rule):
                // an old Pending/Failed row stops driving the badges below
                // the moment the order is genuinely fully paid by some
                // combination of rows — it becomes purely historical at
                // that point (still visible in Payment Details/timeline,
                // never re-appearing as "PAYMENT FAILED"/"PENDING PAYMENT"
                // on an order that's actually settled). Refund status then
                // takes priority over a plain "Paid In Full" label once
                // paid, since "Paid In Full" alone would be misleading on
                // a refunded order.
                $paymentSummary = OrderPaymentSummary::for($order);
                $methodsUsedLabel = \App\Services\PaymentDescriptionPresenter::methodsUsedLabel($paymentSummary->paymentMethodsUsed);
                $hasUnresolvedFailedPayment = $paymentSummary->unresolvedPaymentAttempts->contains(fn ($p) => $p->status === OrderPaymentStatus::Failed);
            @endphp

        {{-- Row 2: status bar — payment pills, PO, and quick links. --}}
        <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 px-5 py-3 bg-gray-50 rounded-b-xl">
                <div class="flex flex-wrap items-center justify-start lg:justify-center gap-2">
                    @if ($paymentSummary->unresolvedPaymentAttempts->isNotEmpty() || $paymentSummary->collectionStatus === OrderPaymentSummary::COLLECTION_PARTIALLY_PAID)
                        <button id="pendingPaymentBtn" type="button"
                            class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-yellow-500 text-white rounded-full">
                            <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                            PENDING PAYMENT
                        </button>
                    @endif

                    @if ($paymentSummary->collectionStatus === OrderPaymentSummary::COLLECTION_PARTIALLY_PAID)
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-orange-700 bg-orange-100 rounded-full">
                            Partial Payment: {{ $methodsUsedLabel }} · {{ \App\Helpers\CustomHelper::formatCurrency($order->total_paid) }}
                        </span>
                        @if ($order->balance_due > 0)
                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-full">
                                Balance Due: {{ \App\Helpers\CustomHelper::formatCurrency($order->balance_due) }}
                            </span>
                        @endif
                    @endif

                    @if ($order->last_payment_status === OrderPaymentStatus::Pending->value && $order->last_payment_type !== 'Card')
                        <button id="addToAccountBtn" type="button"
                            class="px-4 py-1 text-xs font-semibold bg-green-600 text-white rounded-full hover:bg-green-700">
                            Add to Account
                        </button>
                    @endif

                    {{-- On Account: the order's balance was transferred to the
                         customer's credit account (A-R). Canonical "On Account"
                         (indigo) — never "Pending"/"Unpaid". No money was
                         collected on the order; the receivable lives in CRM. --}}
                    @if ($paymentSummary->isOnAccount)
                        <span class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-indigo-500 text-white rounded-full">
                            <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                            On Account · {{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}
                        </span>
                    @endif

                    @if ($order->is_paid && $paymentSummary->refundStatus !== OrderPaymentSummary::REFUND_NONE)
                        {{-- Paid, but refund status takes precedence over a plain
                             "Paid In Full" claim — a fully/partially refunded order
                             must never present as if nothing was returned. --}}
                        <span
                            class="inline-flex items-center px-4 py-1 text-xs font-semibold text-white rounded-full {{ $paymentSummary->refundStatus === OrderPaymentSummary::REFUND_FULL ? 'bg-purple-500' : 'bg-orange-500' }}">
                            <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                            {{ \App\Services\PaymentDescriptionPresenter::orderStatusLabel($paymentSummary) }}
                            · {{ \App\Helpers\CustomHelper::formatCurrency($order->total_paid) }}
                        </span>
                    @elseif ($order->is_paid)
                        <span
                            class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-green-500 text-white rounded-full">
                            <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                            Paid In Full Via -
                            {{ $methodsUsedLabel }}
                            · {{ \App\Helpers\CustomHelper::formatCurrency($order->total_paid) }}
                        </span>
                    @endif

                    @if ($hasUnresolvedFailedPayment)
                        <span
                            class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-red-500 text-white rounded-full">
                            <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                            PAYMENT FAILED
                        </span>
                    @endif

                    @php
                        $lastPaidPayment = $order->lastPaidPayment;
                        try {
                            $voidPaymentUrl = route('admin.order-management.orders.void-payment', $order->unique_id);
                        } catch (\Throwable $e) {
                            $voidPaymentUrl = null;
                        }
                        $canVoid = $order->is_paid
                            && $lastPaidPayment
                            && $voidPaymentUrl
                            && $lastPaidPayment->payment_method === \App\Enums\Orders\OrderPaymentMethod::Card
                            && $lastPaidPayment->transaction_id
                            && $lastPaidPayment->payment_datetime?->isToday();
                    @endphp

                    @if ($canVoid)
                        <button id="voidPaymentBtn" type="button"
                            data-url="{{ $voidPaymentUrl }}"
                            data-amount="{{ $lastPaidPayment->amount }}"
                            data-order-payment-id="{{ $lastPaidPayment->id }}"
                            class="flex items-center px-3 py-1 text-xs font-semibold bg-orange-100 text-orange-800 hover:bg-orange-200 transition rounded-lg">
                            <x-heroicon-o-x-circle class="w-4 h-4 mr-1 text-orange-600" />
                            Void
                        </button>
                    @elseif ($paymentSummary->canRefund())
                        {{-- Canonical refund eligibility: OrderPaymentSummary::canRefund()
                             (a settled payment with a remaining refundable balance).
                             Correct for every method by construction — Account
                             (never settled) and Voided never qualify, so the button
                             no longer shows on an on-account or voided order. Now
                             also matches the refund controller's own eligibility
                             (a partially-paid order with settled money is refundable). --}}
                        <button id="refundPaymentBtn" type="button"
                            class="flex items-center px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-800 hover:bg-gray-200 transition rounded-lg">
                            <x-heroicon-o-credit-card class="w-4 h-4 mr-1 text-gray-600" />
                            {{-- Tier 2: the order's aggregate refund status, not just its last payment row's status. --}}
                            {{ $paymentSummary->refundStatus === \App\Services\Orders\OrderPaymentSummary::REFUND_PARTIAL ? 'Partial Refund' : 'Refund' }}
                        </button>
                    @endif

                    {{-- "Full Refund" must never show for an on-account order (no
                         money was collected on the order — Phase 6/8). Account
                         orders have remaining_amount == 0, which previously
                         satisfied this condition and wrongly surfaced the button. --}}
                    @if ($order->remaining_amount == 0 && ! $paymentSummary->isOnAccount)
                        <button type="button"
                            class="flex items-center px-3 py-1 text-xs font-semibold bg-red-100 text-red-800 hover:bg-red-200 transition rounded-lg">
                            <x-heroicon-o-credit-card class="w-4 h-4 mr-1 text-red-600" />
                            Full Refund
                        </button>
                    @endif

                    @if ($order->payments->isNotEmpty())
                        <button id="paymentDetailsBtn" type="button"
                            class="flex items-center px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-800 hover:bg-gray-200 transition rounded-lg">
                            <x-heroicon-o-information-circle class="w-4 h-4 mr-1 text-gray-600" />
                            Payment Details
                        </button>
                    @endif

                    {{-- POD Reminder Badges --}}
                    @if ($order->podPaymentLink?->pod_reminder_1_sent_at)
                        @php
                            $podR1At = \Carbon\Carbon::parse($order->podPaymentLink->pod_reminder_1_sent_at)->format('m/d/Y h:i A');
                            $podR2At = $order->podPaymentLink->pod_reminder_2_sent_at
                                ? \Carbon\Carbon::parse($order->podPaymentLink->pod_reminder_2_sent_at)->format('m/d/Y h:i A')
                                : null;
                            $podLabel  = 'POD: R1' . ($podR2At ? ', R2' : '');
                            $podTooltip = 'R1: ' . $podR1At . ($podR2At ? ' | R2: ' . $podR2At : '');
                        @endphp
                        <span
                            class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold bg-blue-100 text-blue-700 rounded-full"
                            title="{{ $podTooltip }}">
                            <x-heroicon-o-bell class="w-3 h-3" />
                            {{ $podLabel }}
                        </span>
                    @endif

                    {{-- (PO button moved into the right-aligned links group below) --}}
                </div>

                {{-- PO + quick links — right-aligned as one group (per screenshot). --}}
                <div class="flex items-center gap-4 text-sm text-blue-600 ml-auto">
                    {{-- Purchase Order — a plain "PO" button when none is set, a
                         chip showing the number when one exists; opens #poModal. --}}
                    <button type="button" data-po-open
                        class="inline-flex items-center gap-1.5 h-8 px-2.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 hover:text-gray-700"
                        title="{{ $order->po_id ? 'Edit PO number' : 'Add PO number' }}">
                        <span class="text-[10px] font-bold uppercase tracking-wide text-gray-400">PO</span>
                        @if (!empty($order->po_id))
                            <span class="text-xs font-semibold text-gray-700">{{ $order->po_id }}</span>
                        @endif
                    </button>
                    <a href="{{ route('admin.crm.customers.view', $order->customer?->unique_id) }}"
                        class="inline-flex items-center hover:underline {{ !$order->customer ? 'pointer-events-none opacity-50 cursor-not-allowed' : '' }}"
                        @if (!$order->customer) tabindex="-1" aria-disabled="true" @endif>
                        <x-heroicon-o-user class="w-4 h-4 mr-1" /> Customer Details
                    </a>

                    <form action="{{ route('admin.crm.customers.impersonate-login', $order->customer?->unique_id) }}"
                        method="POST" class="inline-flex items-center">
                        @csrf
                        <button type="submit" class="inline-flex items-center hover:underline">
                            <x-heroicon-o-link class="w-4 h-4 mr-1" /> Website Login
                        </button>
                    </form>
                </div>
            </div>

    </div>
    {{-- /Order Header Section --}}

    {{-- Reorder modal — relocated from the header action column. It is
         fixed-position (order-independent), toggled by #reorderBtn. --}}
    <div id="reorderModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-lg font-semibold">Reorder</h2>
                <button type="button"
                    class="close-reorder-modal-btn text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <form id="reorderForm" class="flex-1 flex flex-col justify-between">
                <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 required">Order Type</label>
                        <select id="orderTypeSelect" name="order_type"
                            class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700"
                            required>
                            <option value="new">New Independent Order</option>
                            <option value="existing_order">Related to Existing Order</option>
                        </select>
                    </div>
                    <div id="existingOrderSection" class="hidden">
                        <div class="text-sm text-green-600 font-semibold">
                            Original Order ID: {{ $order->reference_order_number ?? '—' }}
                        </div>
                        <div class="text-sm text-blue-600 font-semibold">
                            Current Order ID: {{ $order->order_number }}
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                    <button type="button"
                        class="close-reorder-modal-btn px-6 py-3 rounded-lg font-medium text-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-3 rounded-lg font-medium text-md bg-orange-600 text-white hover:bg-orange-700 shadow-sm transition">
                        Continue
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- PO add / edit popup — opened by the "PO" button in the status bar.
         Holds #po_id + the .edit-po-btn save trigger the existing JS binds to,
         so the save behavior is unchanged; only the entry point moved. --}}
    <div id="poModal" style="display:none" class="fixed inset-0 z-[99999] items-center justify-center bg-gray-900/40">
        <div class="bg-white rounded-xl border border-gray-200 shadow-xl p-5 w-[360px] flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Purchase Order</h3>
                <button type="button" data-po-close class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>
            <p class="text-xs text-gray-500">Attach the customer's PO number to this order. It appears on receipts and invoices.</p>
            <input type="text" id="po_id" name="po_id" value="{{ $order->po_id ?? '' }}" data-order-id="{{ $order->id }}"
                placeholder="Enter PO number"
                class="h-9 rounded-lg border border-gray-300 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
            <div class="flex justify-end gap-2">
                <button type="button" data-po-close class="h-9 px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" class="edit-po-btn h-9 px-3.5 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">Save PO</button>
            </div>
        </div>
    </div>

    {{-- Phase 3D — Order Details Refund Summary. Replaces the old,
         outstanding-only "Refund Incomplete" banner with a single,
         always-visible-once-a-refund-exists block covering every figure
         the mission asks for: collected, requested, completed, still
         unprocessed, fees retained, net paid, remaining refundable, and
         overall status — never just a status badge on its own. See
         PaymentAllocationService::totalFeesRetained()/totalRequestedRefund()
         (the two genuinely new aggregates) and OrderPaymentSummary (every
         other figure, already built, never wired into this view). --}}
    @php
        $refundPaymentSummary = \App\Services\Orders\OrderPaymentSummary::for($order);
        $totalFeesRetained = \App\Services\Orders\PaymentAllocationService::totalFeesRetained($order);
        $totalRequestedRefund = \App\Services\Orders\PaymentAllocationService::totalRequestedRefund($order);
        $incompleteRefunds = \App\Services\Orders\PaymentAllocationService::incompleteRefunds($order);
        $totalOutstandingRefund = round($incompleteRefunds->sum(
            fn ($r) => \App\Services\Orders\PaymentAllocationService::outstandingRefundAmount($r)
        ), 2);
        $allRefundEvents = $order->payments()->refund()->with('refundAllocations.originalPayment')->latest('id')->get();

        // The "Resolve Refund" button targets the single most recent
        // incomplete event (already ordered latest-first) — the common
        // case is exactly one outstanding refund at a time; resolving it
        // updates this summary on reload, and a second click handles any
        // further one. Its full retry payload is precomputed here (same
        // shape the AJAX refund response uses) so the button can seed the
        // modal's recovery mode directly, WITHOUT a live submission first.
        $retryTarget = $incompleteRefunds->first();
        $retryTargetPayload = null;
        if ($retryTarget) {
            $retryTargetPayload = [
                'token' => $retryTarget->idempotency_token,
                'amount' => (float) $retryTarget->refund_amount,
                'payment_type' => $retryTarget->payment_method?->value === 'Card' ? 'CreditCard' : ($retryTarget->payment_method?->value ?? 'Cash'),
                'calc_type' => $retryTarget->refund_calculation_type?->value ?? 'standard',
                'sources' => $retryTarget->refundAllocations->map(function ($a) {
                    $original = $a->originalPayment;
                    return [
                        'original_order_payment_id' => $a->original_order_payment_id,
                        'method' => \App\Services\PaymentDescriptionPresenter::methodLabel($original?->payment_method)
                            . ($original?->payment_method === \App\Enums\Orders\OrderPaymentMethod::Card && $original?->card_number ? ' •••• ' . $original->card_number : ''),
                        'amount' => (float) $a->allocated_amount,
                        'success' => $a->status === \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Allocated,
                        'failure_reason' => $a->failure_reason,
                        'needs_manual_review' => \App\Services\Orders\PaymentAllocationService::allocationNeedsManualReview($a),
                    ];
                })->values(),
            ];
        }
    @endphp
    @if ($allRefundEvents->isNotEmpty())
        <div class="bg-white rounded-xl border {{ $totalOutstandingRefund > 0 ? 'border-red-300' : 'border-gray-200' }} shadow-sm mb-6 p-4">
            <div class="flex items-start gap-3">
                @if ($totalOutstandingRefund > 0)
                    @svg('heroicon-o-exclamation-triangle', 'w-6 h-6 text-red-600 shrink-0 mt-0.5')
                @else
                    @svg('heroicon-o-receipt-refund', 'w-6 h-6 text-gray-400 shrink-0 mt-0.5')
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold {{ $totalOutstandingRefund > 0 ? 'text-red-800' : 'text-gray-800' }}">
                        @if ($totalOutstandingRefund > 0)
                            Refund Partially Completed
                        @else
                            {{ $refundPaymentSummary->balanceStatusLabel() }}
                        @endif
                    </p>

                    <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-3 text-xs">
                        <div>
                            <span class="block text-gray-400 uppercase tracking-wide">Payments Collected</span>
                            <span class="font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($refundPaymentSummary->totalSettledPayments) }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 uppercase tracking-wide">Refund Requested</span>
                            <span class="font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($totalRequestedRefund) }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 uppercase tracking-wide">Refund Completed</span>
                            <span class="font-semibold text-green-700">{{ \App\Helpers\CustomHelper::formatCurrency($refundPaymentSummary->totalRefunded) }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 uppercase tracking-wide">Still Unprocessed</span>
                            <span class="font-semibold {{ $totalOutstandingRefund > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ \App\Helpers\CustomHelper::formatCurrency($totalOutstandingRefund) }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 uppercase tracking-wide">Card Fees Retained</span>
                            <span class="font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($totalFeesRetained) }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 uppercase tracking-wide">Net Paid</span>
                            <span class="font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($refundPaymentSummary->netPaid) }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 uppercase tracking-wide">Remaining Refundable</span>
                            <span class="font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($refundPaymentSummary->orderRefundableBalance) }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 uppercase tracking-wide">Overall Status</span>
                            <span class="font-semibold text-gray-900">{{ $refundPaymentSummary->balanceStatusLabel() }}</span>
                        </div>
                    </div>

                    @if ($totalOutstandingRefund > 0)
                        <div class="mt-3 space-y-2 border-t border-red-200 pt-3">
                            @foreach ($incompleteRefunds as $incomplete)
                                @php
                                    $outstanding = \App\Services\Orders\PaymentAllocationService::outstandingRefundAmount($incomplete);
                                    if ($outstanding <= 0) continue;
                                    $stuckOn = $incomplete->refundAllocations
                                        ->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Failed)
                                        ->map(fn ($a) => \App\Services\PaymentDescriptionPresenter::methodLabel($a->originalPayment?->payment_method)
                                            . ($a->originalPayment?->card_number ? ' •••• ' . $a->originalPayment->card_number : ''))
                                        ->implode(', ');
                                @endphp
                                <div class="text-xs text-red-700 flex flex-wrap items-center gap-x-2">
                                    <span class="font-medium">{{ \App\Helpers\CustomHelper::formatCurrency($outstanding) }}</span>
                                    <span>outstanding on the refund initiated {{ $incomplete->refunded_at?->format('M j, Y g:ia') }}</span>
                                    @if ($stuckOn)
                                        <span>— stuck on: {{ $stuckOn }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if ($retryTargetPayload && $retryTargetPayload['token'])
                            <button type="button" id="retryIncompleteRefundBtn"
                                data-retry='@json($retryTargetPayload)'
                                class="mt-3 inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Resolve Refund
                            </button>
                        @else
                            {{-- No idempotency token on the target row (predates
                                 this feature, or was submitted by a caller that
                                 omitted one) — it cannot be safely resumed as a
                                 retry, so offer a plain, ordinary new refund for
                                 the outstanding amount instead. --}}
                            <button type="button" id="startNewRefundForOutstandingBtn"
                                data-outstanding="{{ $totalOutstandingRefund }}"
                                class="mt-3 inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Start a New Refund for the Outstanding Amount
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Phase 3D — Allocation Details: the per-refund-event,
             per-source breakdown (mission §2), reusing the same recovery
             actions Phase 3C already built (enterRecoveryMode()) rather
             than any new endpoint. --}}
        @include('admin.order_management.orders.partials._refund_allocation_details', ['refundEvents' => $allRefundEvents])
    @endif

    {{-- Shared New Fuel Charge modal (Billing Engine commonization) —
         locked-order launch mode: this order + its customer are fixed
         context; same normalized payload / canonical creation path as the
         Dashboard, Fuel Workspace, and CRM launchers. Replaces the legacy
         order-page fuel modal (AlertChargeController now serves Damage only). --}}
    @include('admin.charges._new_fuel_charge_modal', [
        'users' => $employees,
        'fuelNotePresets' => $fuelNotePresets,
        'nfcContext' => 'order_details',
    ])
    {{-- Shared charge-action bundle (Billing Charge Operations
         Commonization): notes/adjust/resolve/uncollectible/history/refund
         modals + behavior for the Billing Engine table rows, and the shared
         payment modal (nested @once include). Replaces the page-local
         beFuel* modals and #beFuelPaymentModal this page used to carry. --}}
    @include('admin.charges._action_modals', [
        'chargeType' => 'fuel',
        'users' => $employees,
    ])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.NewFuelCharge.onCreated = function (charge, continueToPayment) {
                if (continueToPayment) {
                    // Payment posts as a form and redirects back — the
                    // Billing Engine table refreshes on that redirect.
                    window.BillingPayment.openForCharge(charge, @json('Order ' . $order->order_number));
                    return;
                }
                // Brief pause so the pay-later success toast is seen
                // before the Billing Engine table refreshes.
                setTimeout(() => window.location.reload(), 1200);
            };
        });
    </script>

    @php
        // Display tax percentage shared by the Damage Alert modal AND the
        // Order Extension modal further down this file. This definition
        // used to live inside the (since-removed) legacy fuel modal, and
        // deleting that modal broke the extension modal 1,700 lines later
        // — an Undefined variable $taxPercentage 500 on every Order
        // Details load. Keep this defined BEFORE both consumers; do not
        // bury it inside any one modal's markup again.
        $taxPercentage = \App\Helpers\CustomHelper::displayPercentage($sales_tax);
    @endphp

    {{-- Damage Alert Modal --}}
    <div id="orderDamageAlertModal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg border border-gray-200 overflow-hidden flex flex-col max-h-full">
            {{-- Header --}}
            <div class="flex justify-between items-center px-6 pt-4 pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-md bg-red-100">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" class="w-4 h-4 text-red-600"><path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/></svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Add Damage Alert</h2>
                </div>
                <button type="button" onclick="document.getElementById('orderDamageAlertModal').classList.replace('flex','hidden')" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto space-y-5 py-5">
                {{-- Charge Amount --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Charge Amount <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-gray-500 text-sm pointer-events-none">$</span>
                        <input id="orderDamageAmount" type="number" step="0.01" min="0.01" placeholder="0.00"
                            class="w-full pl-7 pr-3 py-3 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Sales Tax Treatment --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sales Tax Treatment</label>
                    <div class="space-y-2 text-sm text-gray-700">
                        <label class="flex items-start gap-2">
                            <input type="radio" name="orderDamageSalesTax" value="add" checked class="mt-1.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <p class="font-medium">Add Sales Tax</p>
                                <p class="text-gray-500">Add {{ $taxPercentage }}% sales tax to the entered amount</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2">
                            <input type="radio" name="orderDamageSalesTax" value="free" class="mt-1.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <p class="font-medium">Tax Free</p>
                                <p class="text-gray-500">No sales tax applied to this charge</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2">
                            <input type="radio" name="orderDamageSalesTax" value="reverse" class="mt-1.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <p class="font-medium">Reverse Sales Tax</p>
                                <p class="text-gray-500">Split entered amount proportionally between base amount and tax</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Charge Reason (fixed, read-only) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Charge Reason <span class="text-red-500">*</span></label>
                    <p class="text-base font-semibold text-red-500">Damages</p>
                </div>

                {{-- Person Responsible --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible <span class="text-red-500">*</span></label>
                    <select id="orderDamagePerson" class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select person responsible</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                    <textarea id="orderDamageNotes" rows="3"
                        class="w-full px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Describe the damage..."></textarea>
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end gap-2 pb-2">
                    <button type="button" onclick="document.getElementById('orderDamageAlertModal').classList.replace('flex','hidden')"
                        class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Cancel</button>
                    <button type="button" id="orderDamageSubmitBtn"
                        class="px-6 py-3 text-md rounded-lg bg-teal-600 text-white hover:bg-teal-700 flex items-center gap-2">
                        <span id="orderDamageBtnText">Add Charge</span>
                        <svg id="orderDamageSpinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Canonical New Task modal (shared with Task Center and Dashboard).
         Variables are passed explicitly because this page already uses
         $categories for product categories. --}}
    @include('admin.tasks.partials._unified_task_modal', $taskModalData)

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        {{-- Billing Info --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Billing Information</h3>
                    <x-heroicon-o-pencil-square class="w-5 h-5 text-blue-500 hover:text-blue-600 cursor-pointer"
                        id="editBillingBtn" data-billing="@json($order->billingAddress ?? [])" />
                </div>
            </div>
            <div class="p-6">
                <div class="text-sm text-gray-700 space-y-2 billing-address-section">
                    <input type="hidden" id="billing_first_name_input"
                        data-first-name="{{ $order->billingAddress->first_name ?? '' }}">
                    <input type="hidden" id="billing_last_name_input"
                        data-last-name="{{ $order->billingAddress->last_name ?? '' }}">
                    <input type="hidden" id="billing_email_input"
                        data-email="{{ $order->billingAddress->email ?? '' }}">
                    <input type="hidden" id="billing_phone_input"
                        data-phone="{{ $order->billingAddress->phone ?? '' }}">
                    <input type="hidden" id="billing_address_input"
                        data-address="{{ $order->billingAddress->address ?? '' }}">
                    <input type="hidden" id="billing_state_input"
                        data-state="{{ $order->billingAddress->state_id ?? '' }}">
                    <input type="hidden" id="billing_city_input" data-city="{{ $order->billingAddress->city ?? '' }}">
                    <input type="hidden" id="billing_zip_code_input"
                        data-zip-code="{{ $order->billingAddress->zip_code ?? '' }}">
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Customer Name:</span>
                        <span class="col-span-2 text-right"
                            id="billing_name">{{ $order->billingAddress->full_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Email:</span>
                        <span class="col-span-2 text-right" id="billing_email">
                            @if ($order->billingAddress->email ?? false)
                                <a href="mailto:{{ $order->billingAddress->email }}"
                                    class="text-blue-600 hover:underline">{{ $order->billingAddress->email }}</a>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Phone:</span>
                        <span class="col-span-2 text-right"
                            id="billing_phone">{{ $order->billingAddress->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Billing Address:</span>
                        <span class="col-span-2 text-right" id="billing_address">
                            @if ($order->billingAddress && $order->billingAddress->full_address)
                                {{ $order->billingAddress->full_address }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    @if ($order->billingAddress && $order->billingAddress->full_address)
                        <div class="grid grid-cols-3" id="billing_map_link">
                            <span></span>
                            <a href="https://maps.google.com/?q={{ urlencode($order->billingAddress->full_address) }}"
                                 class="text-blue-600 text-xs hover:underline col-span-2 text-right">See on
                                maps</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Shipping Info --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Delivery Information</h3>
                    <x-heroicon-o-pencil-square class="w-5 h-5 text-blue-500 hover:text-blue-600 cursor-pointer"
                        id="editShippingBtn" />
                </div>
            </div>
            <div class="p-6">
                <input type="hidden" id="shipping_first_name_input"
                    data-first-name="{{ $order->shippingAddress->first_name ?? '' }}">
                <input type="hidden" id="shipping_last_name_input"
                    data-last-name="{{ $order->shippingAddress->last_name ?? '' }}">
                <input type="hidden" id="shipping_email_input" data-email="{{ $order->shippingAddress->email ?? '' }}">
                <input type="hidden" id="shipping_phone_input" data-phone="{{ $order->shippingAddress->phone ?? '' }}">
                <input type="hidden" id="shipping_address_input"
                    data-address="{{ $order->shippingAddress->address ?? '' }}">
                <input type="hidden" id="shipping_state_input"
                    data-state="{{ $order->shippingAddress->state_id ?? '' }}">
                <input type="hidden" id="shipping_city_input" data-city="{{ $order->shippingAddress->city ?? '' }}">
                <input type="hidden" id="shipping_zip_code_input"
                    data-zip-code="{{ $order->shippingAddress->zip_code ?? '' }}">

                <div
                    class="text-sm text-gray-700 space-y-1 shipping-address-section {{ ($order->shippingAddress && $order->shippingAddress->isSameAs($order->billingAddress)) ? 'hidden' : '' }}">
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Customer Name:</span>
                        <span class="col-span-2 text-right"
                            id="shipping_name">{{ $order->shippingAddress->full_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Email:</span>
                        <span class="col-span-2 text-right" id="shipping_email">
                            @if ($order->shippingAddress->email ?? false)
                                <a href="mailto:{{ $order->shippingAddress->email }}"
                                    class="text-blue-600 hover:underline">{{ $order->shippingAddress->email }}</a>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Phone:</span>
                        <span class="col-span-2 text-right"
                            id="shipping_phone">{{ $order->shippingAddress->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Delivery Address:</span>
                        <span class="col-span-2 text-right" id="shipping_address">
                            @if ($order->shippingAddress && $order->shippingAddress->full_address)
                                {{ $order->shippingAddress->full_address }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    @if ($order->shippingAddress && $order->shippingAddress->full_address)
                        <div class="grid grid-cols-3" id="shipping_map_link">
                            <span></span>
                            <a href="https://maps.google.com/?q={{ urlencode($order->shippingAddress->full_address) }}"
                                 class="text-blue-600 text-xs hover:underline col-span-2 text-right">See on
                                maps</a>
                        </div>
                    @endif
                </div>
                <p class="text-sm text-gray-600 shipping-address-section {{ ($order->shippingAddress && $order->shippingAddress->isSameAs($order->billingAddress)) ? '' : 'hidden' }}"
                    id="shipping_same_as_billing">Same as Billing</p>

            </div>
        </div>

        {{-- History --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">History</h3>
                </div>
            </div>
            <div class="p-4 max-h-60 overflow-y-auto">
                @php
                    // Payment-related entries render via the presenter's
                    // structured timelineEntry() (headline/method/amount as
                    // distinct fields); every other entry keeps today's
                    // plain description — no behavior change for non-payment
                    // history rows.
                    $orderHistory = $order->history->map(function ($h) {
                        $timeline = \App\Services\PaymentDescriptionPresenter::timelineEntry($h);
                        return [
                            'headline'    => $timeline['headline'],
                            'method'      => $timeline['method'],
                            'amount'      => $timeline['amount'],
                            'sign'        => $timeline['sign'],
                            'dot'         => $timeline['dot'],
                            'note'        => $timeline['note'],
                            'detail'      => $timeline['detail'],
                            'created_at'  => $h->created_at,
                            'is_pod'      => false,
                        ];
                    });

                    $podActivities = ($order->podPaymentLink?->activities ?? collect())->map(fn($a) => [
                        'headline'    => 'POD — ' . $a->event->label(),
                        'method'      => null,
                        'amount'      => null,
                        'sign'        => null,
                        'dot'         => 'info',
                        'note'        => null,
                        'detail'      => null,
                        'created_at'  => $a->created_at,
                        'is_pod'      => true,
                    ]);

                    $allHistory = $orderHistory->concat($podActivities)->sortBy('created_at')->values();

                    $dotClasses = [
                        'good'    => 'bg-green-500',
                        'info'    => 'bg-blue-500',
                        'bad'     => 'bg-red-500',
                        'neutral' => 'bg-gray-400',
                    ];
                @endphp
                <ul class="text-sm text-gray-700 space-y-3">
                    @forelse ($allHistory as $entry)
                        <li class="flex gap-2">
                            <span class="mt-1.5 w-2 h-2 rounded-full flex-shrink-0 {{ $dotClasses[$entry['dot']] ?? $dotClasses['neutral'] }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="font-medium {{ $entry['is_pod'] ? 'text-blue-700' : 'text-gray-800' }}">
                                        {{ $entry['headline'] }}
                                        @if ($entry['method'])
                                            <span class="font-normal text-gray-500">· {{ $entry['method'] }}</span>
                                        @endif
                                    </span>
                                    @if ($entry['amount'] !== null)
                                        <span class="font-semibold flex-shrink-0 {{ $entry['sign'] === 'neg' ? 'text-red-600' : 'text-green-700' }}">
                                            {{ $entry['sign'] === 'neg' ? '-' : '' }}{{ \App\Helpers\CustomHelper::formatCurrency($entry['amount']) }}
                                        </span>
                                    @endif
                                </div>
                                @if ($entry['note'])
                                    <div class="text-xs text-gray-500 mt-0.5">Note: {{ $entry['note'] }}</div>
                                @endif
                                @if ($entry['detail'])
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $entry['detail'] }}</div>
                                @endif
                                <div class="text-xs text-gray-400 mt-0.5">
                                    {{ \App\Helpers\CustomHelper::formatDateTime($entry['created_at']) }}
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-gray-400 text-sm">No history available.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>




    {{-- Rental Equipment (extension child orders only): read-only context
         rendered through referenceOrder — no parent line is ever copied onto
         this order's own order_products rows. --}}
    @if ($order->isExtensionChild() && $order->referenceOrder)
        @php
            $parentRentalLines = $order->referenceOrder->products->filter(
                fn($line) => ($line->product_data['product_type'] ?? null) === 'Rental',
            );
        @endphp
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h3 class="text-base font-semibold text-gray-800">Rental Equipment</h3>
                <div class="text-sm text-gray-600">
                    Parent Order:
                    <a href="{{ route('admin.order-management.orders.edit', $order->referenceOrder->unique_id) }}"
                        class="text-blue-600 font-semibold hover:underline">
                        #{{ $order->reference_order_number }}
                    </a>
                </div>
            </div>
            @forelse ($parentRentalLines as $parentLine)
                @php
                    $parentLineEquipment = $parentLine->equipment ?? $parentLine->softAssignment?->equipment;
                    $parentLineEquipmentId =
                        $parentLine->equipment_details['equipment_id'] ?? $parentLineEquipment?->equipment_id;
                    $parentLineCategory =
                        $parentLineEquipment?->category_name ?: $parentLine->product?->categories?->first()?->title;
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-1 text-sm py-2 {{ !$loop->first ? 'border-t border-gray-100' : '' }}">
                    <div>
                        <span class="text-gray-500">Product:</span>
                        <span class="font-medium text-gray-800">{{ $parentLine->product_name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Equipment:</span>
                        <span class="font-medium text-gray-800">{{ $parentLineEquipment?->equipment_name ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Equipment ID:</span>
                        @if ($parentLineEquipmentId)
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-mono font-medium bg-gray-100 text-gray-700 border">{{ $parentLineEquipmentId }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-gray-500">Category:</span>
                        <span class="font-medium text-gray-800">{{ $parentLineCategory ?: '—' }}</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400">No rental lines on the parent order.</p>
            @endforelse
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 space-y-6">
        <h3 class="text-base font-semibold text-gray-800">Equipment Orders & Delivery Schedule</h3>

        {{-- Equipment Info --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            @foreach ($order->products as $index => $orderProduct)
                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div class="w-[150px] h-[150px] bg-gray-100 flex items-center justify-center text-gray-400">
                            @if ($orderProduct?->product?->image_url)
                                <img src="{{ $orderProduct->product->image_url }}"
                                    alt="{{ $orderProduct->product_name }}"
                                    class="object-contain w-full h-full rounded" />
                            @else
                                Image not available
                            @endif
                        </div>
                        <div>
                            @php
                                if ($orderProduct?->product) {
                                    $href = route(
                                        'admin.product-management.products.edit',
                                        $orderProduct->product->unique_id,
                                    );
                                }
                            @endphp
                            <a href="{{ $href ?? 'javascript:void(0);' }}"
                                class="text-blue-600 font-semibold hover:underline">
                                {{ $orderProduct->product_name }} -
                                {{ ucwords($orderProduct->product_data['product_variant'] ?? '') }}
                            </a>
                            @if ($orderProduct->product_data['product_type'] === 'Rental')
                                @php
                                    $disableEquipmentLink = in_array($orderProduct->delivery_status, ['Completed', 'Close as Completed'], true)
                                        && $orderProduct->pickup_status === 'Completed';
                                @endphp
                                <p class="text-sm text-gray-500">
                                    Equipment:
                                    @if ($orderProduct->equipment)
                                        <span class="ml-2 inline-flex items-center gap-1">
                                            @if ($disableEquipmentLink)
                                                <span class="text-xs text-gray-400">
                                                    {{ $orderProduct->equipment->equipment_name ?? '—' }} ||
                                                    ({{ $orderProduct->equipment->equipment_id ?? '—' }})
                                                </span>
                                            @else
                                                <a href="{{ route('admin.maintenance-management.equipment.edit', $orderProduct->equipment->unique_id) }}"
                                                     class="text-xs text-green-600 hover:underline">
                                                    {{ $orderProduct->equipment->equipment_name ?? '—' }} ||
                                                    ({{ $orderProduct->equipment->equipment_id ?? '—' }})
                                                </a>
                                            @endif
                                            @if (!$disableEquipmentLink)
                                                <button type="button"
                                                    class="remove-equipment-btn text-red-500 hover:text-red-700"
                                                    data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                                    title="Remove equipment">
                                                    <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                                                </button>
                                            @endif
                                        </span>
                                    @else
                                        @if (!empty($orderProduct?->softAssignment))
                                            @php
                                                $softUnique =
                                                    $orderProduct?->softAssignment?->equipment->unique_id ?? null;
                                            @endphp
                                            <span class="ml-2 inline-flex items-center gap-1">
                                                @if ($disableEquipmentLink)
                                                    <span class="text-xs text-gray-400">
                                                        {{ $orderProduct?->softAssignment?->equipment->equipment_name ?? '—' }} ||
                                                        ({{ $orderProduct?->softAssignment?->equipment->equipment_id ?? '—' }})
                                                    </span>
                                                @else
                                                    <a href="{{ route('admin.maintenance-management.equipment.edit', $softUnique) }}"
                                                       class="text-xs text-blue-600 hover:underline">
                                                        {{ $orderProduct?->softAssignment?->equipment->equipment_name ?? '—' }} ||
                                                        ({{ $orderProduct?->softAssignment?->equipment->equipment_id ?? '—' }})
                                                    </a>
                                                @endif
                                                @if (!$disableEquipmentLink)
                                                    <button type="button"
                                                        class="remove-equipment-btn text-red-500 hover:text-red-700"
                                                        data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                                        title="Remove equipment">
                                                        <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                                                    </button>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    @endif
                                </p>
                            @endif

                        </div>
                    </div>

                    <div class="border rounded-xl p-4 text-sm text-gray-700 space-y-2 bg-gray-50">
                        <div class="flex justify-between">
                            <span>Product Cost(x{{ $orderProduct->quantity ?? 1 }})</span>
                            <span>{{ \App\Helpers\CustomHelper::formatCurrency($orderProduct->price) }}</span>
                        </div>

                        {{-- @if (!empty($orderProduct->service_method))
                            <div class="flex flex-col gap-y-1">
                            <div class="text-xs flex justify-between items-center">
                                <span class="underline">Service:</span>
                            </div>
                            <ul class="flex flex-col">
                                <li class="text-xs before:content-['-'] before:pr-1">
                                {{ $orderProduct->service_method }}
                                </li>
                            </ul>
                            </div>
                        @endif

                        @if (!empty($orderProduct->service_option))
                            <div class="flex flex-col gap-y-1">
                            <div class="text-xs flex justify-between items-center">
                                <span class="underline">Delivery:</span>
                            </div>
                            <ul class="flex flex-col">
                                <li class="text-xs before:content-['-'] before:pr-1">
                                {{ $orderProduct->service_option }}
                                </li>
                            </ul>
                            </div>
                        @endif --}}

                        @if (!empty($orderProduct->distance_range))
                            <div class="flex flex-col gap-y-1">
                                <div class="text-xs underline">
                                    Distance Range:
                                </div>
                                <ul class="pl-5 list-disc text-gray-600 text-sm">
                                    <li class="flex justify-between">
                                        <span>
                                            {{ ucfirst($orderProduct->distance_type) }}
                                            ({{ ucfirst($orderProduct->distance_range) }})
                                        </span>
                                        <span>
                                            {{ \App\Helpers\CustomHelper::formatCurrency($orderProduct->product_data['service_option_price'] ?? 0) }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        @endif



                        @if ($orderProduct->product_data && count($orderProduct->product_data))
                            <div class="flex justify-between">
                                <span class="underline">Options</span>
                            </div>
                            <ul class="pl-5 list-disc text-gray-600 text-sm">
                                @foreach ($orderProduct->product_data['product_rental_items_prices'] as $rentalKey => $rentalPrice)
                                    <li class="flex justify-between">
                                        <span>
                                            @php
                                                $case = collect(
                                                    \App\Enums\Products\ProductCustomStaticLabel::cases(),
                                                )->firstWhere('name', $rentalKey);
                                                $quantity = $case ? $orderProduct->quantity : 1;
                                            @endphp

                                            {{ $case?->label() ?? ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey))) }}
                                            <span class="text-xs text-gray-400">
                                                (x{{ $quantity }})
                                            </span>
                                        </span>
                                        <span>
                                            {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice) }}
                                        </span>
                                    </li>
                                @endforeach
                                @foreach ($orderProduct->product_data['product_option_items'] as $option)
                                    <li class="flex justify-between">
                                        <span>
                                            {{ $option['name'] }}
                                            <span
                                                class="text-xs text-gray-400">(x{{ $orderProduct->quantity ?? 1 }})</span>
                                        </span>
                                        <span>
                                            {{ \App\Helpers\CustomHelper::formatCurrency($option['price'] ?? 0) }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="flex justify-between font-semibold">
                            <span>Qty - {{ $orderProduct->quantity }}</span>
                            <span>
                                Subtotal: {{ \App\Helpers\CustomHelper::formatCurrency($orderProduct->sub_total) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="px-2 py-2 rounded-t-lg border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800 under">{{ $orderProduct->product_name }}
                                </h3>
                                <input type="hidden" class="order-product-unique-id"
                                    value="{{ $orderProduct->unique_id }}">
                            </div>
                        </div>
                        <div class="p-4">
                            @if ($orderProduct->product_data['product_type'] === 'Rental')
                                <div class="flex items-center justify-between">
                                </div>
                                {{-- Delivery Schedule --}}
                                <div class="space-y-2 mb-4" x-data="{ deliveryStatus: '{{ $orderProduct->delivery_status ?? 'Pending' }}' }">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                        <x-heroicon-o-arrow-right-circle class="w-5 h-5" /> Delivery Schedule
                                    </div>
                                    <div class="  bg-white border rounded-xl p-3 flex flex-wrap gap-3 items-center">
                                        <div class="flex flex-wrap items-center gap-2 w-full">
                                            <!-- Date -->
                                            <div class="flex flex-col items-start min-w-[100px] max-w-[120px] flex-[0.9]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="delivery_date_{{ $orderProduct->unique_id }}">Date</label>
                                                <input type="text" id="delivery_date_{{ $orderProduct->unique_id }}"
                                                    data-format="{{ config('app.date.js_date_format') }}"
                                                    value="{{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date) : '' }}"
                                                    placeholder="Select date"
                                                    {{-- data-min-date="{{ now()->format(config('app.date.db_date_format')) }}" --}}
                                                    class="datepicker delivery_date border rounded px-3 py-3 text-xs w-full" />
                                            </div>
                                            <!-- Time -->
                                            <div class="flex flex-col items-start min-w-[80px] max-w-[100px] flex-[0.8]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="delivery_time_{{ $orderProduct->unique_id }}">Time</label>
                                                <input type="text" placeholder="Select time"
                                                    id="delivery_time_{{ $orderProduct->unique_id }}"
                                                    value="{{ $orderProduct->delivery_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->delivery_time) : '' }}"
                                                    class="delivery_time border rounded px-3 py-3 text-xs w-full" />
                                            </div>
                                            <!-- Type (fixed width, non-stretch) -->
                                            <div class="flex flex-col items-start flex-shrink-0 w-[44px]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="delivery_transport_mode-btn-{{ $orderProduct->unique_id }}">Type</label>
                                                <div id="delivery_transport_mode-{{ $orderProduct->unique_id }}"
                                                    x-data="{ selected: '{{ $orderProduct->delivery_transport_mode ?? 'Store' }}', open: false }" class="relative w-full">
                                                    <button
                                                        id="delivery_transport_mode-btn-{{ $orderProduct->unique_id }}"
                                                        type="button" @click="open = !open"
                                                        class="border rounded px-px-3 py-3 text-xs w-full flex items-center justify-center gap-1 focus:outline-none delivery_transport_mode">
                                                        <template x-if="selected === 'Store'">
                                                            <x-heroicon-o-building-storefront class="w-4 h-4"
                                                                x-bind:class="(deliveryStatus === 'Completed' ||
                                                                    deliveryStatus === 'Close as Completed') ?
                                                                'text-green-600' :
                                                                'text-yellow-600'" />
                                                        </template>
                                                        <template x-if="selected === 'Truck'">
                                                            <x-heroicon-o-truck class="w-4 h-4"
                                                                x-bind:class="(deliveryStatus === 'Completed' ||
                                                                    deliveryStatus === 'Close as Completed') ?
                                                                'text-green-600' :
                                                                'text-yellow-600'" />
                                                        </template>
                                                    </button>
                                                    <div x-show="open" @click.away="open = false"
                                                        class="absolute z-10 mt-1 w-full bg-white border rounded shadow-lg">
                                                        <ul>
                                                            <li>
                                                                <button type="button"
                                                                    @click="selected = 'Store'; open = false;  $nextTick(() => $refs.deliveryTypeInput.dispatchEvent(new Event('change')));"
                                                                    class="w-full flex items-center justify-center px-3 py-3 hover:bg-yellow-100">
                                                                    <x-heroicon-o-building-storefront class="w-4 h-4"
                                                                        x-bind:class="(deliveryStatus === 'Completed' ||
                                                                            deliveryStatus === 'Close as Completed') ?
                                                                        'text-green-600' : 'text-yellow-600'" />
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button type="button"
                                                                    @click="selected = 'Truck'; open = false; $nextTick(() => $refs.deliveryTypeInput.dispatchEvent(new Event('change')));"
                                                                    class="w-full flex items-center justify-center px-3 py-3 hover:bg-yellow-100">
                                                                    <x-heroicon-o-truck class="w-4 h-4"
                                                                        x-bind:class="(deliveryStatus === 'Completed' ||
                                                                            deliveryStatus === 'Close as Completed') ?
                                                                        'text-green-600' : 'text-yellow-600'" />
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <input type="hidden" name="type" class="delivery_transport_mode"
                                                        :value="selected" x-ref="deliveryTypeInput">
                                                </div>
                                            </div>
                                            <!-- Status -->
                                            <div class="flex flex-col items-start min-w-[70px] flex-1">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="delivery_status_{{ $orderProduct->unique_id }}">Status</label>
                                                <select id="delivery_status_{{ $orderProduct->unique_id }}"
                                                    x-model="deliveryStatus"
                                                    class="delivery_status border rounded px-3 py-3 text-xs w-full">
                                                    <option value="Pending"
                                                        {{ ($orderProduct->delivery_status ?? '') === 'Pending' ? 'selected' : '' }}>
                                                        Pending</option>
                                                    <option value="Completed"
                                                        {{ ($orderProduct->delivery_status ?? '') === 'Completed' ? 'selected' : '' }}
                                                        {{ ($orderProduct->delivery_status ?? '') === 'Close as Completed' ? 'disabled' : '' }}>
                                                        Completed</option>
                                                    <option value="Close as Completed"
                                                        {{ ($orderProduct->delivery_status ?? '') === 'Close as Completed' ? 'selected' : '' }}
                                                        {{ ($orderProduct->delivery_status ?? '') === 'Completed' ? 'disabled' : '' }}>
                                                        Close as Completed</option>
                                                    <option value="Reschedule"
                                                        {{ ($orderProduct->delivery_status ?? '') === 'Reschedule' ? 'selected' : '' }}>
                                                        Reschedule</option>
                                                </select>
                                            </div>
                                            <!-- Delivery Store -->
                                            <div class="flex flex-col items-start min-w-[70px] flex-1">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="delivery_store_id_{{ $orderProduct->unique_id }}">Delivery Store</label>
                                                <select id="delivery_store_id_{{ $orderProduct->unique_id }}"
                                                    class="delivery_store_id border rounded px-3 py-3 text-xs w-full">
                                                    <option value="" disabled selected>Select Delivery Store</option>
                                                    @foreach ($stores as $storeItem)
                                                        <option value="{{ $storeItem->id }}"
                                                            {{ $orderProduct->delivery_store_id == $storeItem->id ? 'selected' : '' }}>
                                                            {{ $storeItem->store_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <!-- Driver -->
                                            <div class="flex flex-col items-start min-w-[90px] flex-1">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="delivery_by_{{ $orderProduct->unique_id }}">Driver / Tech</label>
                                                <select id="delivery_by_{{ $orderProduct->unique_id }}"
                                                    class="delivery_by border rounded px-3 py-3 text-xs w-full"
                                                    {{ !empty($orderProduct->delivery_by) ? 'disabled' : '' }}>
                                                    <option value="" disabled {{ empty($orderProduct->delivery_by) ? 'selected' : '' }}>Select</option>
                                                    @foreach ($drivers as $driver)
                                                        <option value="{{ $driver->id }}"
                                                            {{ $orderProduct->delivery_by == $driver->id ? 'selected' : '' }}>
                                                            {{ $driver->first_name }} {{ $driver->last_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Return Schedule --}}
                                @php
                                    $allowPickupComplete = in_array($orderProduct->delivery_status, ['Completed', 'Close as Completed'], true);
                                @endphp
                                <div class="space-y-2 mb-4" x-data="{ pickupStatus: '{{ $orderProduct->pickup_status ?? 'Pending' }}' }">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                        <x-heroicon-o-arrow-left-circle class="w-5 h-5" /> Return Schedule
                                    </div>
                                    <div class="bg-white border rounded-xl p-3 flex flex-wrap gap-3 items-center">
                                        <div class="flex flex-wrap items-center gap-2 w-full">
                                            <!-- Date -->
                                            <div class="flex flex-col items-start min-w-[100px] max-w-[120px] flex-[0.9]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="pickup_date_{{ $orderProduct->unique_id }}">Date</label>
                                                <input type="text" id="pickup_date_{{ $orderProduct->unique_id }}"
                                                    data-format="{{ config('app.date.js_date_format') }}"
                                                    placeholder="Select date"
                                                    value="{{ $orderProduct->pickup_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->pickup_date) : '' }}"
                                                    {{-- data-min-date="{{ now()->format(config('app.date.db_date_format')) }}" --}}
                                                    class="datepicker pickup_date border rounded px-3 py-3 text-xs w-full" />
                                            </div>
                                            <!-- Time -->
                                            <div class="flex flex-col items-start min-w-[80px] max-w-[100px] flex-[0.8]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="pickup_time_{{ $orderProduct->unique_id }}">Time</label>
                                                <input type="text" id="pickup_time_{{ $orderProduct->unique_id }}"
                                                    placeholder="Select time"
                                                    value="{{ $orderProduct->pickup_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->pickup_time) : '' }}"
                                                    class="pickup_time border rounded px-3 py-3 text-xs w-full" />
                                            </div>
                                            <!-- Type (fixed width, non-stretch) -->
                                            <div class="flex flex-col items-start flex-shrink-0 w-[44px]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="pickup_transport_mode-btn-{{ $orderProduct->unique_id }}">Type</label>
                                                <div id="pickup_transport_mode_{{ $orderProduct->unique_id }}"
                                                    x-data="{ selected: '{{ $orderProduct->pickup_transport_mode ?? 'Store' }}', open: false }" class="relative w-full">
                                                    <button id="pickup_transport_mode-btn-{{ $orderProduct->unique_id }}"
                                                        type="button" @click="open = !open"
                                                        class="border rounded px-3 py-3 text-xs w-full flex items-center justify-center gap-1 focus:outline-none pickup_transport_mode">
                                                        <template x-if="selected === 'Store'">
                                                            <x-heroicon-o-building-storefront class="w-4 h-4"
                                                                x-bind:class="(pickupStatus === 'Completed' ||
                                                                    pickupStatus === 'Close as Completed') ?
                                                                'text-green-600' :
                                                                'text-yellow-600'" />
                                                        </template>
                                                        <template x-if="selected === 'Truck'">
                                                            <x-heroicon-o-truck class="w-4 h-4"
                                                                x-bind:class="(pickupStatus === 'Completed' ||
                                                                    pickupStatus === 'Close as Completed') ?
                                                                'text-green-600' :
                                                                'text-yellow-600'" />
                                                        </template>
                                                    </button>
                                                    <div x-show="open" @click.away="open = false"
                                                        class="absolute z-10 mt-1 w-full bg-white border rounded shadow-lg">
                                                        <ul>
                                                            <li>
                                                                <button type="button"
                                                                    @click="selected = 'Store'; open = false; $nextTick(() => $refs.pickupTypeInput.dispatchEvent(new Event('change')));"
                                                                    class="w-full flex items-center justify-center px-3 py-3 hover:bg-yellow-100">
                                                                    <x-heroicon-o-building-storefront class="w-4 h-4"
                                                                        x-bind:class="(pickupStatus === 'Completed' ||
                                                                            pickupStatus === 'Close as Completed') ?
                                                                        'text-green-600' : 'text-yellow-600'" />
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button type="button"
                                                                    @click="selected = 'Truck'; open = false; $nextTick(() => $refs.pickupTypeInput.dispatchEvent(new Event('change')));"
                                                                    class="w-full flex items-center justify-center px-3 py-3 hover:bg-yellow-100">
                                                                    <x-heroicon-o-truck class="w-4 h-4"
                                                                        x-bind:class="(pickupStatus === 'Completed' ||
                                                                            pickupStatus === 'Close as Completed') ?
                                                                        'text-green-600' : 'text-yellow-600'" />
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <input type="hidden" name="type" class="pickup_transport_mode"
                                                        :value="selected" x-ref="pickupTypeInput">
                                                </div>
                                            </div>
                                            <!-- Status -->
                                            <div class="flex flex-col items-start min-w-[70px] flex-1">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="pickup_status_{{ $orderProduct->unique_id }}">Status</label>
                                                <select id="pickup_status_{{ $orderProduct->unique_id }}"
                                                    x-model="pickupStatus"
                                                    class="pickup_status border rounded px-3 py-3 text-xs w-full">
                                                    <option value="Pending"
                                                        {{ ($orderProduct->pickup_status ?? '') === 'Pending' ? 'selected' : '' }}>
                                                        Pending</option>
                                                    <option value="Completed"
                                                        {{ ($orderProduct->pickup_status ?? '') === 'Completed' ? 'selected' : '' }}
                                                        {{ $allowPickupComplete ? '' : 'disabled' }}>
                                                        Completed</option>
                                                    {{-- <option value="Reschedule"
                                {{ ($orderProduct->pickup_status ?? '') === 'Reschedule' ? 'selected' : '' }}>
                                Reschedule</option> --}}
                                                </select>
                                            </div>
                                            <!-- Return Store -->
                                            <div class="flex flex-col items-start min-w-[70px] flex-1">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="pickup_store_id_{{ $orderProduct->unique_id }}">Return Store</label>
                                                <select id="pickup_store_id_{{ $orderProduct->unique_id }}"
                                                    class="pickup_store_id border rounded px-3 py-3 text-xs w-full">
                                                    <option value="" disabled selected>Select Return Store</option>
                                                    @foreach ($stores as $storeItem)
                                                        <option value="{{ $storeItem->id }}"
                                                            {{ $orderProduct->pickup_store_id == $storeItem->id ? 'selected' : '' }}>
                                                            {{ $storeItem->store_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <!-- Driver -->
                                            <div class="flex flex-col items-start min-w-[90px] flex-1">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="pickup_by_{{ $orderProduct->unique_id }}">Driver / Tech</label>
                                                <select id="pickup_by_{{ $orderProduct->unique_id }}"
                                                    class="pickup_by border rounded px-3 py-3 text-xs w-full"
                                                    {{ !empty($orderProduct->pickup_by) ? 'disabled' : '' }}>
                                                    <option value="" disabled {{ empty($orderProduct->pickup_by) ? 'selected' : '' }}>Select</option>
                                                    @foreach ($drivers as $driver)
                                                        <option value="{{ $driver->id }}"
                                                            {{ $orderProduct->pickup_by == $driver->id ? 'selected' : '' }}>
                                                            {{ $driver->first_name }} {{ $driver->last_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Delivery Steps + Return Steps --}}
                                @php
                                    // ── Delivery Steps: 4 required gates ──────────────────────────────────
                                    $dlTermsComplete     = $order->terms_status?->isAccepted() || $order->terms_status?->isExempt();
                                    $dlLicenseComplete   = $order->licenseMedia->isNotEmpty();
                                    $dlChecklistComplete = $orderProduct->is_delivered == 1;
                                    $dlVideoComplete     = $orderProduct->deliveryMedia->count() > 0;

                                    $showDeliveryTrophy  = $dlTermsComplete && $dlLicenseComplete && $dlChecklistComplete && $dlVideoComplete;
                                    $dlAnyDone           = $dlTermsComplete || $dlLicenseComplete || $dlChecklistComplete || $dlVideoComplete;
                                    $dlInProgress        = $dlAnyDone && !$showDeliveryTrophy;

                                    // ── Return Steps: 2 required gates; hidden until delivery is done ──────
                                    $deliveryIsComplete  = $orderProduct->delivery_status === 'Completed' || $orderProduct->is_delivered == 1;
                                    $rtChecklistComplete = $orderProduct->is_returned == 1;
                                    $rtVideoComplete     = $orderProduct->pickupMedia->count() > 0;

                                    $showReturnTrophy    = $rtChecklistComplete && $rtVideoComplete;
                                    $rtAnyDone           = $rtChecklistComplete || $rtVideoComplete;
                                    $rtInProgress        = $deliveryIsComplete && $rtAnyDone && !$showReturnTrophy;
                                @endphp
                                <div class="flex gap-3 flex-wrap items-stretch">
                                    {{-- Checklist / Video badges --}}
                                    <div class="border rounded-xl px-8 py-4 bg-white">
                                        <div class="flex justify-center gap-4 text-center text-xs font-medium text-gray-700">
                                            <!-- Checklist -->
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="text-gray-500 leading-none">Checklist</span>
                                                <div class="flex gap-1">
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded text-white text-xs font-bold
                                                        {{ $orderProduct->is_delivered == 1 ? 'bg-green-500 hover:bg-green-600 cursor-pointer' : 'bg-red-500 cursor-not-allowed' }}"
                                                        @if ($orderProduct->is_delivered == 1) onclick="openChecklistModal({{ $orderProduct->id }})" @endif>D</span>
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded text-white text-xs font-bold
                                                        {{ $orderProduct->is_returned == 1 ? 'bg-green-500 hover:bg-green-600 cursor-pointer' : 'bg-red-500 cursor-not-allowed' }}"
                                                        @if ($orderProduct->is_returned == 1) onclick="openChecklistModal({{ $orderProduct->id }})" @endif>R</span>
                                                </div>
                                            </div>

                                            <!-- Video -->
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="text-gray-500 leading-none">Video</span>
                                                <div class="flex gap-1">
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded text-white text-xs font-bold
                                                        {{ $orderProduct->deliveryMedia->count() ? 'bg-green-500 cursor-pointer' : 'bg-red-500 cursor-not-allowed' }}"
                                                        @if ($orderProduct->deliveryMedia->count()) onclick="openAllMedia({{ $orderProduct->id }}, 'delivery')" @endif>D</span>
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded text-white text-xs font-bold
                                                        {{ $orderProduct->pickupMedia->count() ? 'bg-green-500 cursor-pointer' : 'bg-red-500 cursor-not-allowed' }}"
                                                        @if ($orderProduct->pickupMedia->count()) onclick="openAllMedia({{ $orderProduct->id }}, 'pickup')" @endif>R</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Legend -->
                                        <div class="flex items-center justify-center gap-2 text-xs text-gray-400 mt-2 pt-1 border-t border-gray-100">
                                            <span class="flex items-center gap-0.5"><span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500"></span>Completed</span>
                                            <span>|</span>
                                            <span class="flex items-center gap-0.5"><span class="inline-block w-1.5 h-1.5 rounded-full bg-red-500"></span>Pending</span>
                                        </div>
                                    </div>

                                    {{-- Delivery Steps + Return Steps boxes --}}
                                    <div class="flex gap-3 flex-wrap flex-1">

                                    {{-- Delivery Steps --}}
                                    <div class="border rounded-xl px-4 py-3 bg-white flex-1 min-w-[200px]">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Delivery Steps</span>
                                        </div>
                                        @if($showDeliveryTrophy)
                                            {{-- State 3: all 4 gates complete --}}
                                            <div class="flex flex-col items-center justify-center py-1 gap-0.5">
                                                <span class="text-2xl leading-none">🏆</span>
                                                @if($orderProduct->deliveryEmployee)
                                                    <span class="text-lg text-gray-600 font-medium text-center">{{ $orderProduct->deliveryEmployee->full_name }}</span>
                                                @else
                                                    <span class="text-xs text-gray-400">Complete</span>
                                                @endif
                                            </div>
                                        @elseif($dlInProgress)
                                            {{-- State 2: at least one gate done; show two-column checklist --}}
                                            <div class="flex flex-col gap-0.5 text-xs">
                                                <div class="flex justify-between gap-4">
                                                    <span class="text-gray-500">Terms Signed</span>
                                                    <span class="font-medium whitespace-nowrap {{ $dlTermsComplete ? 'text-green-600' : 'text-orange-500' }}">{{ $dlTermsComplete ? 'Completed' : 'Pending' }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4">
                                                    <span class="text-gray-500">License Uploaded</span>
                                                    <span class="font-medium whitespace-nowrap {{ $dlLicenseComplete ? 'text-green-600' : 'text-orange-500' }}">{{ $dlLicenseComplete ? 'Completed' : 'Pending' }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4">
                                                    <span class="text-gray-500">Checklist Completed</span>
                                                    <span class="font-medium whitespace-nowrap {{ $dlChecklistComplete ? 'text-green-600' : 'text-orange-500' }}">{{ $dlChecklistComplete ? 'Completed' : 'Pending' }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4">
                                                    <span class="text-gray-500">Video Uploaded</span>
                                                    <span class="font-medium whitespace-nowrap {{ $dlVideoComplete ? 'text-green-600' : 'text-orange-500' }}">{{ $dlVideoComplete ? 'Completed' : 'Pending' }}</span>
                                                </div>
                                            </div>
                                        @else
                                            {{-- State 1: nothing started yet --}}
                                            <p class="text-xs text-gray-400 italic text-center py-1">Pending</p>
                                        @endif
                                    </div>

                                    {{-- Return Steps --}}
                                    <div class="border rounded-xl px-4 py-3 bg-white flex-1 min-w-[200px]">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Return Steps</span>
                                        </div>
                                        @if(!$deliveryIsComplete)
                                            {{-- Delivery not done yet — keep panel quiet --}}
                                            <p class="text-xs text-gray-400 italic text-center py-1">Pending</p>
                                        @elseif($showReturnTrophy)
                                            {{-- State 3: all return gates complete --}}
                                            <div class="flex flex-col items-center justify-center py-1 gap-0.5">
                                                <span class="text-2xl leading-none">🏆</span>
                                                @if($orderProduct->pickupEmployee)
                                                    <span class="text-lg text-gray-600 font-medium text-center">{{ $orderProduct->pickupEmployee->full_name }}</span>
                                                @else
                                                    <span class="text-xs text-gray-400">Complete</span>
                                                @endif
                                            </div>
                                        @elseif($rtInProgress)
                                            {{-- State 2: at least one return gate done --}}
                                            <div class="flex flex-col gap-0.5 text-xs">
                                                <div class="flex justify-between gap-4">
                                                    <span class="text-gray-500">Checklist Completed</span>
                                                    <span class="font-medium whitespace-nowrap {{ $rtChecklistComplete ? 'text-green-600' : 'text-orange-500' }}">{{ $rtChecklistComplete ? 'Completed' : 'Pending' }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4">
                                                    <span class="text-gray-500">Video Uploaded</span>
                                                    <span class="font-medium whitespace-nowrap {{ $rtVideoComplete ? 'text-green-600' : 'text-orange-500' }}">{{ $rtVideoComplete ? 'Completed' : 'Pending' }}</span>
                                                </div>
                                            </div>
                                        @else
                                            {{-- State 1: delivered but no return activity yet --}}
                                            <p class="text-xs text-gray-400 italic text-center py-1">Pending</p>
                                        @endif
                                    </div>

                                    </div>
                                </div>{{-- close flex gap-3 flex-wrap items-stretch --}}

                                {{-- Hidden JSON for THIS product --}}
                                {{-- Hidden JSON --}}
                                <script type="application/json" id="media-{{ $orderProduct->id }}-delivery">
                                    {!! $orderProduct->deliveryMedia->map(fn($m) => ['url'=> optional($m->media)->url, 'type'=>$m->type])->toJson() !!}
                                </script>

                                <script type="application/json" id="media-{{ $orderProduct->id }}-pickup">
                                    {!! $orderProduct->pickupMedia->map(fn($m) => ['url'=> optional($m->media)->url, 'type'=>$m->type])->toJson() !!}
                                </script>
                            @else
                                <div class="text-gray-500 text-sm">
                                    <p>No delivery or return schedules available for this product.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                <hr class="col-span-full my-2 border-t border-gray-300" />
            @endforeach
        </div>

        @php
    // Phase 3D fix: was $refunds->sum('refund_amount') — the raw requested
    // amount of every refund row, including ones that partially or fully
    // failed. Order::total_refunded (PaymentAllocationService::totalSuccessfulRefunded())
    // is the allocation-aware, successful-only figure, so this box no
    // longer disagrees with the Refund Summary above it.
    $totalRefunded = $order->total_refunded;

    $finalGrandTotal = max(0, $order->grand_total - $totalRefunded);

    // Final Phase — Financial Integrity Audit fix: the itemized list below
    // used to loop $refunds directly and re-derive each row's tax split
    // with its own ad-hoc CustomHelper::calculateRefundSalesTax() call —
    // a second, independent formula that (a) duplicated logic that already
    // lives in ReceiptService::refundDetails()/PaymentAllocationService,
    // and (b) summed tax across EVERY refund row including partially/fully
    // failed ones, while $totalRefunded above is already allocation-aware
    // (successful-only) — the two totals could disagree the moment a
    // refund partially failed. Sourcing both from the same canonical,
    // Allocated-only line set (already built for the receipt) closes both
    // gaps at once and satisfies the "one presenter for every refund
    // display" rule.
    $orderDetailsRefundLines = \App\Services\ReceiptService::refundDetails($order);
    $totalRefundSalesTax = collect($orderDetailsRefundLines)->sum('tax_refunded');
    $totalRefundSubtotal = collect($orderDetailsRefundLines)->sum(fn ($l) => $l['amount'] - $l['tax_refunded']);
@endphp

        {{-- Summary & Notes --}}
        <div class="grid grid-cols-4 gap-4 items-stretch">

            {{-- Terms & License --}}
            @if (!empty($order->pending_terms_content))
                <div class="border rounded-xl p-3 bg-white shadow-sm flex flex-col justify-center">
                    <div class="flex gap-6 text-center text-xs font-medium text-gray-700 justify-center">
                        <!-- Terms -->
                        <div class="flex flex-col items-center gap-1.5">
                            <span class="text-gray-400 uppercase tracking-wide text-[10px]">Terms</span>
                            <div class="flex gap-1">
                                @if ($order->terms_status->isPending())
                                    <a href="{{ route('front.terms-and-conditions.index', $order->unique_id) }}" title="View Terms">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-500 text-white shadow-sm">
                                            <x-heroicon-o-x-mark class="w-4 h-4" />
                                        </span>
                                    </a>
                                    <button id="send-terms"
                                        data-url="{{ route('admin.order-management.orders.send-terms', ['unique_id' => $order->unique_id]) }}">
                                        <span class="relative group inline-flex items-center justify-center w-7 h-7 rounded-lg bg-yellow-500 text-white shadow-sm">
                                            <x-heroicon-o-chat-bubble-left-right class="w-4 h-4" />
                                            <span class="absolute top-full mt-1 hidden group-hover:block px-2 py-1 bg-gray-800 text-white text-xs rounded shadow-lg whitespace-nowrap z-10">
                                                Send terms signature request
                                            </span>
                                        </span>
                                    </button>
                                @else
                                    <a href="{{ route('front.terms-and-conditions.index', $order->unique_id) }}" title="View Terms">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-green-500 text-white shadow-sm">
                                            <x-heroicon-o-check class="w-4 h-4" />
                                        </span>
                                    </a>
                                @endif
                            </div>
                            @php
                                $termsSendTimestamps = collect([
                                    $order->terms_first_sent_at,
                                    $order->terms_second_sent_at,
                                    $order->terms_third_sent_at,
                                    $order->last_terms_sms_sent_at,
                                ])->filter()->unique()->sort()->values();
                            @endphp
                            @foreach ($termsSendTimestamps as $ts)
                                <span class="text-yellow-600 text-[10px]">
                                    {{ \App\Helpers\CustomHelper::formatDateTime12Hour($ts) }}
                                </span>
                            @endforeach
                            @if (!$order->terms_status->isPending() && !empty($order->terms_accepted_at))
                                <span class="text-green-600 text-[10px]">
                                    Signed {{ \App\Helpers\CustomHelper::formatDateTime12Hour($order->terms_accepted_at) }}
                                </span>
                            @endif
                        </div>

                        <div class="w-px bg-gray-100 self-stretch"></div>

                        <!-- License -->
                        <div class="flex flex-col items-center gap-1.5">
                            <span class="text-gray-400 uppercase tracking-wide text-[10px]">License</span>
                            <div class="flex gap-1">
                                @if ($order->licenseMedia->isNotEmpty())
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-green-500 text-white shadow-sm cursor-pointer"
                                        onclick="openAllMedia({{ $order->id }}, 'license')">
                                        <x-heroicon-o-check class="w-4 h-4" />
                                    </span>
                                    <script type="application/json" id="media-{{ $order->id }}-license">
                                        {!! $order->licenseMedia->map(fn($m) => ['url' => optional($m->media)->url, 'type' => $m->type, 'side' => $m->side])->toJson() !!}
                                    </script>
                                @else
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-500 text-white shadow-sm">
                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="flex items-center justify-center gap-2 text-[10px] text-gray-400 mt-3 pt-2 border-t border-gray-100">
                        <span class="flex items-center gap-1"><span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500"></span>Completed</span>
                        <span>|</span>
                        <span class="flex items-center gap-1"><span class="inline-block w-1.5 h-1.5 rounded-full bg-red-500"></span>Pending</span>
                    </div>
                </div>
            @endif

            <div
                class="bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-2 shadow-sm flex flex-col text-sm text-gray-700">
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span>{{ \App\Helpers\CustomHelper::formatCurrency($order->subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Taxes:</span>
                    <span>{{ \App\Helpers\CustomHelper::formatCurrency($order->tax_amount) }}</span>
                </div>
                <div class="flex justify-between font-bold text-gray-900 border-t pt-2">
                    <span>Grand Total:</span>
                    <span>{{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}</span>
                </div>

                @if(!empty($orderDetailsRefundLines))

                    <div class="border-t pt-2 space-y-3">

                        @foreach($orderDetailsRefundLines as $line)

                            <div class="flex justify-between items-start">

                                <div>

                                    <div class="font-medium text-green-700">
                                        Refund
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ $line['date']?->format('M j, Y g:ia') }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        Method: {{ $line['method'] }}
                                    </div>

                                </div>

                                <div class="text-right">

                                    <div class="font-semibold text-green-600">
                                        -{{ \App\Helpers\CustomHelper::formatCurrency($line['amount'] - $line['tax_refunded']) }}
                                    </div>

                                    <div class="text-xs text-blue-600">
                                        Tax:
                                        -{{ \App\Helpers\CustomHelper::formatCurrency($line['tax_refunded']) }}
                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                    {{-- $totalRefundSubtotal / $totalRefundSalesTax are computed once, above,
                         from $orderDetailsRefundLines (ReceiptService::refundDetails()) —
                         the same canonical, Allocated-only line set the itemized list
                         above and the printed/emailed receipt both use. --}}

                    <div class="flex justify-between font-bold text-gray-900 border-t pt-3">
                        <span>Total Refunded:</span>

                        <span class="text-green-600">
                            -{{ \App\Helpers\CustomHelper::formatCurrency($totalRefundSubtotal) }}
                        </span>
                    </div>

                    <div class="flex justify-between font-bold text-gray-900">
                        <span>Total Tax Refunded:</span>

                        <span class="text-blue-600">
                            -{{ \App\Helpers\CustomHelper::formatCurrency($totalRefundSalesTax) }}
                        </span>
                    </div>

                    @php
                        $finalGrandTotal = max(
                            0,
                            $order->grand_total - $totalRefunded
                        );
                    @endphp

                    <div class="flex justify-between font-bold text-gray-900 border-t pt-3 text-base">
                        <span>Final Grand Total:</span>

                        <span>
                            {{ \App\Helpers\CustomHelper::formatCurrency($finalGrandTotal) }}
                        </span>
                    </div>

                @endif

            </div>

            {{-- Notes (col-span-2 = 50%) --}}
            <div class="col-span-2 bg-white rounded-xl border border-gray-200 p-4 shadow-sm flex flex-col relative">
                <h2 class="text-black font-semibold text-lg mb-2">
                    Order / Delivery Instructions
                </h2>
                <button
                    class="absolute top-3 right-3 flex items-center gap-1 px-2 py-1.5 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700"
                    id="addNoteBtn">
                    <x-heroicon-o-plus class="w-4 h-4" /> Add Note
                </button>
                <div class="p-6 max-h-60 overflow-y-auto" id="noteDiv">
                    <x-admin.order-management.orders.order-notes-list :notes="$order->unified_notes" />
                </div>
            </div>
        </div>{{-- close grid grid-cols-4 --}}

        {{-- Store Credit as a PRE-TAX DISCOUNT (not a payment). The retired
             "Customer Credit" tender panel (redeem → presentational
             balance_due − credit, no tax recompute) has been removed; this
             canonical discount panel is now the single application path and
             folds in the read-only credit-balance cards. --}}
        {{-- Reveal gating: hidden until the header "Store Credit" trigger is
             clicked, but auto-shown when a discount is already applied so an
             existing credit never disappears. --}}
        @php
            $scHasApplied = \App\Models\Discounts\ProductDiscount::query()
                ->where('target_type', 'order')->where('target_id', $order->id)
                ->where('discount_type', 'store_credit')->where('status', 'applied')->exists();
        @endphp
        <div id="storeCreditSection" class="grid md:grid-cols-1 gap-4 mt-4 scroll-mt-6 @if(!$scHasApplied) hidden @endif">
            @include('admin.order_management.orders.partials._store_credit_discount_panel')
        </div>

        {{-- Resolution Center — Phase 3.3. Hidden until the header "Resolution"
             trigger is clicked; auto-shown when a case already exists. --}}
        @can('resolution_center.use')
        @php
            $resHasCase = \App\Models\Customers\ResolutionCase::where('order_id', $order->id)->exists();
        @endphp
        <div id="resolutionCenterSection" class="grid md:grid-cols-1 gap-4 mt-4 scroll-mt-6 @if(!$resHasCase) hidden @endif">
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <h2 class="text-black font-semibold text-lg mb-3">Resolution Center</h2>
                <p class="text-sm text-gray-500 mb-3">Guided, policy-driven help for a customer cancellation, refund, or other resolution request on this order.</p>
                {{ html()->form('POST', route('admin.resolution-center.start', $order->unique_id))->open() }}
                <div class="flex gap-3 items-start">
                    {!! html()->textarea('issue', null)->class('flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->rows(2)->placeholder('What is the customer requesting?')->required() !!}
                    {!! html()->select('scenario_key', collect(\App\Services\ResolutionCenter\ResolutionScenarioRegistry::all())->map(fn($scenario) => $scenario->label()))
                            ->class('border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                            ->value(\App\Services\ResolutionCenter\CancellationRefundScenario::KEY) !!}
                    <button type="submit" class="px-5 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm whitespace-nowrap">Start Resolution Case</button>
                </div>
                {{ html()->form()->close() }}
            </div>
        </div>
        @endcan

        @if (!empty($payments) && $payments->isNotEmpty())
            {{--  Order Extra Payments --}}
            <div class="grid md:grid-cols-1 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-2 shadow-sm flex flex-col relative">
                    <h2 class="text-black font-semibold text-lg mb-2">
                        Order Extra Payments
                    </h2>

                    <div class=" max-h-60 overflow-y-auto">
                        <x-admin.order-management.orders.order-extra-payments-list :payments="$payments" />
                    </div>
                </div>
            </div>
        @endif

        {{-- POD Payment Links History --}}
        {{-- @if ($order->podPaymentLink)
            @php
                $podActivities = $order->podPaymentLink->activities ?? collect();
                $podPaymentUrl = route('front.checkout.order-payment-form', ['order' => encrypt($order->unique_id)]);
                $podLinkModel  = $order->podPaymentLink;
                $podCurStatus  = $podLinkModel->pod_status instanceof \App\Enums\Orders\PodPaymentLinkStatus
                    ? $podLinkModel->pod_status
                    : \App\Enums\Orders\PodPaymentLinkStatus::from($podLinkModel->pod_status);
                [$podCurBg, $podCurBorder, $podCurTextColor, $podCurIcon] = match ($podCurStatus) {
                    \App\Enums\Orders\PodPaymentLinkStatus::Pending     => ['bg-yellow-50',  'border-yellow-300', 'text-yellow-800', 'heroicon-o-clock'],
                    \App\Enums\Orders\PodPaymentLinkStatus::Opened      => ['bg-blue-50',    'border-blue-300',   'text-blue-800',   'heroicon-o-eye'],
                    \App\Enums\Orders\PodPaymentLinkStatus::Expired     => ['bg-red-50',     'border-red-300',    'text-red-800',    'heroicon-o-x-circle'],
                    \App\Enums\Orders\PodPaymentLinkStatus::Reactivated => ['bg-orange-50',  'border-orange-300', 'text-orange-800', 'heroicon-o-arrow-path'],
                    \App\Enums\Orders\PodPaymentLinkStatus::Completed   => ['bg-green-50',   'border-green-300',  'text-green-800',  'heroicon-o-check-circle'],
                };
                $podTimestamps = array_filter([
                    $podLinkModel->payment_link_created_at  ? 'Created: '     . \Carbon\Carbon::parse($podLinkModel->payment_link_created_at)->format('m/d/Y h:i A')  : null,
                    $podLinkModel->payment_link_opened_at   ? 'Opened: '      . \Carbon\Carbon::parse($podLinkModel->payment_link_opened_at)->format('m/d/Y h:i A')   : null,
                    $podLinkModel->pod_expired_at           ? 'Expired: '     . \Carbon\Carbon::parse($podLinkModel->pod_expired_at)->format('m/d/Y h:i A')           : null,
                    $podLinkModel->pod_reactivated_at       ? 'Reactivated: ' . \Carbon\Carbon::parse($podLinkModel->pod_reactivated_at)->format('m/d/Y h:i A')       : null,
                    $podLinkModel->pod_payment_completed_at ? 'Paid: '        . \Carbon\Carbon::parse($podLinkModel->pod_payment_completed_at)->format('m/d/Y h:i A') : null,
                ]);
            @endphp
            <div class="grid md:grid-cols-1 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                    <h2 class="text-black font-semibold text-lg mb-3">POD Payment Links</h2>


                    <div class="flex flex-wrap items-center gap-3 mb-4 p-3 rounded-lg border {{ $podCurBg }} {{ $podCurBorder }}">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold {{ $podCurTextColor }} {{ $podCurBg }} border {{ $podCurBorder }}">
                            <x-dynamic-component :component="$podCurIcon" class="w-4 h-4" />
                            {{ ucfirst($podCurStatus->value) }}
                        </span>
                        @foreach ($podTimestamps as $ts)
                            <span class="text-xs text-gray-500">{{ $ts }}</span>
                            @if (!$loop->last)<span class="text-gray-300 text-xs">|</span>@endif
                        @endforeach
                        @if ($podLinkModel->payment_link_open_count > 0)
                            <span class="ml-auto text-xs text-gray-500">Opened {{ $podLinkModel->payment_link_open_count }}×</span>
                        @endif
                    </div>
                    @if ($podActivities->isNotEmpty())
                        <div class="overflow-x-auto max-h-60 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                                        <th class="pb-2 pr-4">#</th>
                                        <th class="pb-2 pr-4">Time</th>
                                        <th class="pb-2 pr-4">Status / Event</th>
                                        <th class="pb-2">Payment Link</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($podActivities->sortBy('id') as $i => $activity)
                                        <tr>
                                            <td class="py-2 pr-4 text-gray-400 text-xs">{{ $i + 1 }}</td>
                                            <td class="py-2 pr-4 text-gray-600 whitespace-nowrap text-xs">
                                                {{ \Carbon\Carbon::parse($activity->created_at)->format('m/d/Y h:i A') }}
                                            </td>
                                            <td class="py-2 pr-4">
                                                @php
                                                    $evt = $activity->event instanceof \App\Enums\Orders\PodPaymentLinkEvent
                                                        ? $activity->event
                                                        : \App\Enums\Orders\PodPaymentLinkEvent::from($activity->event);
                                                    [$evtBg, $evtText] = match ($evt) {
                                                        \App\Enums\Orders\PodPaymentLinkEvent::LinkCreated       => ['bg-gray-100',   'text-gray-700'],
                                                        \App\Enums\Orders\PodPaymentLinkEvent::PaymentLinkSent   => ['bg-blue-100',   'text-blue-700'],
                                                        \App\Enums\Orders\PodPaymentLinkEvent::LinkOpened        => ['bg-indigo-100', 'text-indigo-700'],
                                                        \App\Enums\Orders\PodPaymentLinkEvent::Reminder1Sent,
                                                        \App\Enums\Orders\PodPaymentLinkEvent::Reminder2Sent,
                                                        \App\Enums\Orders\PodPaymentLinkEvent::Reminder3Sent,
                                                        \App\Enums\Orders\PodPaymentLinkEvent::Reminder4Sent     => ['bg-yellow-100', 'text-yellow-800'],
                                                        \App\Enums\Orders\PodPaymentLinkEvent::DayBeforeSent,
                                                        \App\Enums\Orders\PodPaymentLinkEvent::FinalReminderSent,
                                                        \App\Enums\Orders\PodPaymentLinkEvent::LastDitchSent     => ['bg-orange-100', 'text-orange-700'],
                                                        \App\Enums\Orders\PodPaymentLinkEvent::ManualResend      => ['bg-teal-100',   'text-teal-700'],
                                                        \App\Enums\Orders\PodPaymentLinkEvent::PaymentCompleted  => ['bg-green-100',  'text-green-700'],
                                                        \App\Enums\Orders\PodPaymentLinkEvent::OrderExpired      => ['bg-red-100',    'text-red-700'],
                                                        \App\Enums\Orders\PodPaymentLinkEvent::OrderReactivated  => ['bg-purple-100', 'text-purple-700'],
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $evtBg }} {{ $evtText }}">
                                                    {{ $evt->label() }}
                                                </span>
                                            </td>
                                            <td class="py-2">
                                                <a href="{{ $podPaymentUrl }}"
                                                   target="_blank"
                                                   class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 hover:underline">
                                                    <x-heroicon-o-link class="w-3.5 h-3.5" />
                                                    Open Payment Page
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400">No activity recorded yet.</p>
                    @endif
                </div>
            </div>
        @endif
        --}}

        {{-- Billing Engine — consolidated view of billing_charges for this order.
             Hidden until a header charge trigger (Fuel/Damage/Order Enhancement)
             is clicked; auto-shown when the order already has billing charges. --}}
        <div id="billingEngineSection" class="scroll-mt-6 @if($billingCharges->isEmpty()) hidden @endif">
        @include('admin.order_management.orders.partials._billing_engine', ['billingCharges' => $billingCharges])
        </div>

        @include('admin.order_management.orders.partials._extension_delete_modal')

        {{-- Delete Order --}}
        @php
            // Extension children share one lifecycle with their Rental
            // Extension charge — deleting this order must also remove the
            // charge from the parent, via the coordinated confirmation flow.
            $extDeleteCharge = \App\Services\ExtensionTransactionService::chargeForChild($order);
        @endphp
        <div class="flex justify-end mt-6">
            <button type="button" id="delete-order-btn"
                data-unique-id="{{ $order->unique_id }}"
                data-ext-child="{{ $extDeleteCharge ? '1' : '' }}"
                data-ext-parent="{{ $order->reference_order_number }}"
                data-ext-number="{{ $order->order_number }}"
                data-ext-paystate="{{ $extDeleteCharge ? \App\Services\ExtensionTransactionService::paymentState($extDeleteCharge, $order) : '' }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold shadow-sm transition">
                <x-heroicon-o-trash class="w-4 h-4" />
                Delete Order
            </button>
        </div>

    </div>

    <!-- Extension Charge Modal -->
    <div id="extensionChargeModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-md shadow-lg flex flex-col">
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-lg font-semibold text-gray-800">Add Order Enhancement</h2>
                <button type="button" id="closeExtensionModalX"
                    class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <select id="extDescSelect"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="">Select a description…</option>
                        <option value="Rental Extension">Rental Extension</option>
                        <option value="Delivery / Pickup Added">Delivery / Pickup Added</option>
                        <option value="Chains / Binders / Straps">Chains / Binders / Straps</option>
                        <option value="Retail Item(s)">Retail Item(s)</option>
                        {{-- saved custom options injected by JS before this marker --}}
                        <option value="__other__">Other…</option>
                    </select>

                    {{-- "Other" custom description input --}}
                    <div id="extCustomDescGroup" class="hidden mt-2 space-y-2">
                        <input type="text" id="extCustomDesc"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                            placeholder="Type your description…" maxlength="255" />
                        <button type="button" id="extSaveDescBtn"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-md border border-teal-500 text-teal-600 hover:bg-teal-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V7l-4-4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 3v4H7V3"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 12v6m-3-3h6"/></svg>
                            Save to List
                        </button>
                    </div>
                    <p id="extDescError" class="text-xs text-red-500 mt-1 hidden">Description is required.</p>
                </div>

                {{-- Base Amount --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Base Amount <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center border border-gray-300 rounded-md px-3 py-2 focus-within:ring-1 focus-within:ring-blue-500">
                        <span class="text-gray-500 text-sm mr-1">$</span>
                        <input type="number" id="extBaseAmount" step="0.01" min="0.01"
                            class="flex-1 text-sm focus:outline-none" placeholder="0.00" />
                    </div>
                    <p id="extAmountError" class="text-xs text-red-500 mt-1 hidden">Please enter a valid amount.</p>
                </div>

                {{-- Sales Tax --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sales Tax</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="extTaxTreatment" id="extTaxAdd" value="add" checked
                                class="accent-teal-500" />
                            <span class="text-sm text-gray-700">Add {{ $taxPercentage }}% Tax</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="extTaxTreatment" id="extTaxFree" value="free"
                                class="accent-teal-500" />
                            <span class="text-sm text-gray-700">No Tax</span>
                        </label>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 space-y-1 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Base Amount:</span>
                        <span id="extSummaryBase">$0.00</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Sales Tax ({{ $taxPercentage }}%):</span>
                        <span id="extSummaryTax">$0.00</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-800 border-t border-gray-200 pt-2 mt-1">
                        <span>Total:</span>
                        <span id="extSummaryTotal">$0.00</span>
                    </div>
                </div>

                {{-- Person Responsible --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Person Responsible <span class="text-red-500">*</span>
                    </label>
                    <select id="extPerson"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <option value="">Select Person</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                    <p id="extPersonError" class="text-xs text-red-500 mt-1 hidden">Please select a person responsible.</p>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                    <textarea id="extNotes" rows="2"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none resize-none"
                        placeholder="Any additional notes..." maxlength="1000"></textarea>
                </div>
            </div>
            <div class="flex flex-wrap justify-end gap-3 p-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button" id="closeExtensionModalBtn"
                    class="px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 text-sm hover:bg-gray-100">
                    Cancel
                </button>
                {{-- Deferred path: creates the extension with the charge left
                     outstanding (Pay on Delivery placeholder) — payable later
                     from the Billing Engine row exactly as today --}}
                <button type="button" id="extSubmitBtn"
                    class="px-5 py-2 rounded-md border border-orange-300 bg-white text-orange-600 text-sm font-semibold hover:bg-orange-50 disabled:opacity-60">
                    <span id="extBtnText">Create Order Enhancement — Pay Later</span>
                    <span id="extBtnSpinner" class="hidden">Creating…</span>
                </button>
                {{-- Immediate path: creates the extension, then advances
                     straight into the existing Make a Payment modal --}}
                <button type="button" id="extPayNowBtn"
                    class="px-6 py-2 rounded-md bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600 disabled:opacity-60">
                    <span id="extPayNowText">Continue to Payment</span>
                    <span id="extPayNowSpinner" class="hidden">Creating…</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Add/Edit Note Modal -->
    <div id="noteModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-md shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 id="noteModalTitle" class="text-lg font-semibold">Add Note</h2>
                <button type="button" onclick="closeNoteModal()"
                    class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1 flex flex-col justify-between',
                    'id' => 'noteForm',
                ])->open() }}
            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="user_id">User</label>
                    {!! html()->select('user_id', $employees->pluck('full_name', 'id')->toArray())->id('user_id')->class([
                            'w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                        ])->required() !!}
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="note_text">Note</label>
                    {!! html()->textarea('note')->id('note_text')->class(['w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500'])->attribute('rows', 5)->placeholder('Enter note...')->required() !!}
                </div>
            </div>
            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button" onclick="closeNoteModal()"
                    class="px-6 py-3 rounded-lg font-medium text-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-3 rounded-lg font-medium text-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Save
                </button>
            </div>
            {{ html()->form()->close() }}
        </div>
    </div>

    <!-- Address Edit Modal (Reusable for Billing & Delivery) -->
    <div id="addressModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 id="addressModalTitle" class="text-lg font-semibold">Edit Address</h2>
                <button type="button" onclick="closeModal()"
                    class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'addressForm',
                ])->open() }}

            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- First Name -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="first_name">First name</label>
                        {!! html()->text('first_name', old('first_name'))->class([
                                'w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('first_name'),
                            ])->attributes([
                                'placeholder' => 'Enter first name',
                                'id' => 'first_name',
                                'autocomplete' => 'given-name',
                            ])->required() !!}
                    </div>
                    <!-- Last Name -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="last_name">Last name</label>
                        {!! html()->text('last_name', old('last_name'))->class([
                                'w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('last_name'),
                            ])->attributes([
                                'placeholder' => 'Enter last name',
                                'id' => 'last_name',
                                'autocomplete' => 'family-name',
                            ])->required() !!}
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Email -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="email">Email</label>
                        {!! html()->email('email', old('email'))->class([
                                'w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('email'),
                            ])->attributes([
                                'placeholder' => 'Enter email',
                                'id' => 'email',
                                'autocomplete' => 'email',
                                'readonly' => 'readonly',
                                'disabled' => 'disabled',
                            ])->required() !!}
                    </div>
                    <!-- Phone -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="phone">Phone</label>
                        {!! html()->text('phone', old('phone'))->class([
                                'masked-phone w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('phone'),
                            ])->attributes([
                                'placeholder' => '(xxx) xxx-xxxx',
                                'id' => 'phone',
                                'autocomplete' => 'tel',
                            ])->required() !!}
                    </div>
                    <!-- Type (hidden, set by JS) -->
                    <input type="hidden" name="type" id="type" value="{{ old('type') }}">
                </div>

                <!-- Address -->
                <div>
                    <label class="text-sm font-medium text-gray-700" for="address">Address</label>
                    {!! html()->text('address', old('address'))->class([
                            'w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500',
                            'border-red-500' => $errors->has('address'),
                        ])->attributes([
                            'placeholder' => 'Enter address',
                            'id' => 'address',
                            'autocomplete' => 'street-address',
                        ])->required() !!}
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- State -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="state">State</label>
                        {!! html()->select(
                                'state_id',
                                $states->mapWithKeys(fn($state) => [$state->id => $state->name])->toArray(),
                                old('state', $customer->state_id ?? ''),
                            )->id('state')->class([
                                'w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                                'border-red-500' => $errors->has('state'),
                            ])->required() !!}
                    </div>
                    <!-- City -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="city">City</label>
                        {!! html()->text('city', old('city'))->class([
                                'w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('city'),
                            ])->attributes([
                                'placeholder' => 'Enter city',
                                'id' => 'city',
                                'autocomplete' => 'address-level2',
                            ])->required() !!}
                    </div>
                    <!-- Zip Code -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="zip_code">Zip code</label>
                        {!! html()->number('zip_code', old('zip_code'))->class([
                                'w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('zip_code'),
                            ])->attributes([
                                'placeholder' => 'Enter zip code',
                                'id' => 'zip_code',
                                'autocomplete' => 'postal-code',
                            ])->required() !!}
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button" onclick="closeModal()"
                    class="px-6 py-3 rounded-lg font-medium text-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-3 rounded-lg font-medium text-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Save
                </button>
            </div>
            </form>
        </div>
    </div>

    <!-- Process Payment Modal -->
    <div id="processPaymentModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center"
        data-grand-total="{{ $order->grand_total }}"
        {{-- Canonical settled total from OrderPaymentSummary ($paymentSummary,
             built unconditionally in the header PHP block). Replaces the old
             $totalPartialPaid inline sum that only counted PartialPayment-status
             rows — settled includes those plus Paid/Invoice rows, so the JS
             balance-due math (grandTotal - totalPaid) stays right even when a
             partially-collected order has mixed-status settled rows. NOTE:
             never write the at-sign php directive token inside a Blade
             comment — the compiler extracts php blocks BEFORE stripping
             comments, and a stray token here once paired with a distant
             endphp and silently vaporized ~385 lines of this template. --}}
        data-total-paid="{{ $paymentSummary->totalSettledPayments }}">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 id="processPaymentTitle" class="text-lg font-semibold">Process Payment</h2>
                <button type="button"
                    class="close-process-payment-modal-btn text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'paymentForm',
                ])->open() }}
            {{-- Regenerated fresh each time the modal opens (never on retry
                 of the same submit) — lets the server recognize a duplicate
                 double-click/network-retry of the same payment attempt
                 without blocking a genuinely new, separate payment. --}}
            <input type="hidden" name="idempotency_token" id="paymentIdempotencyToken" value="">

            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">

                <!-- Balance Due banner (always visible) -->
                <div class="flex justify-between items-center px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Balance Due</span>
                    <span id="balanceDueDisplay" class="text-lg font-bold text-blue-700"></span>
                </div>

                <!-- Full / Partial Payment toggle -->
                <div class="flex gap-3">
                    <label id="labelFullPayment"
                        class="flex-1 flex items-center gap-3 p-3 border-2 border-teal-500 bg-teal-50 rounded-lg cursor-pointer transition-colors select-none">
                        <input type="radio" name="payment_mode" value="full" id="paymentModeFull" class="hidden" checked />
                        <span id="radioFullOuter" class="w-4 h-4 rounded-full border-2 border-teal-500 flex items-center justify-center flex-shrink-0">
                            <span class="w-2 h-2 rounded-full bg-teal-500" id="radioFullDot"></span>
                        </span>
                        <span class="text-sm font-semibold text-gray-800">Full Payment</span>
                    </label>
                    <label id="labelPartialPayment"
                        class="flex-1 flex items-center gap-3 p-3 border-2 border-gray-200 bg-white rounded-lg cursor-pointer transition-colors select-none">
                        <input type="radio" name="payment_mode" value="partial" id="paymentModePartial" class="hidden" />
                        <span id="radioPartialOuter" class="w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0">
                            <span class="w-2 h-2 rounded-full bg-teal-500 hidden" id="radioPartialDot"></span>
                        </span>
                        <span class="text-sm font-semibold text-gray-500">Partial Payment</span>
                    </label>
                </div>

                <!-- Partial payment fields (hidden by default) -->
                <div id="partialPaymentFields" class="hidden space-y-3 bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <div class="flex justify-between text-sm font-medium text-gray-700">
                        <span>Amount Owed:</span>
                        <span id="amountOwedDisplay" class="font-semibold text-gray-900"></span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount</label>
                        <div class="flex items-center border border-gray-300 rounded-md px-3 py-2 bg-white focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
                            <span class="text-gray-500 text-sm mr-1">$</span>
                            <input type="number" id="partialPaymentAmount" step="0.01" min="0.01"
                                   class="flex-1 text-sm focus:outline-none bg-transparent" placeholder="0.00" />
                        </div>
                        <p id="partialAmountError" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>
                    <div class="flex justify-between text-sm font-medium text-gray-700 border-t pt-2">
                        <span>Remaining Balance:</span>
                        <span id="remainingBalanceDisplay" class="font-semibold text-gray-500">—</span>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="space-y-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Payment Method </label>
                        {!! html()->select('payment_type', \App\Enums\Customers\PaymentMethod::options())->id('payment_type')->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')->required() !!}
                    </div>

                    <!-- Cheque Number (hidden by default) -->
                    <div id="chequeNumberField" class="mb-4 hidden">
                        <label for="cheque_number" class="block text-sm font-medium text-gray-700 mb-1 ">
                            Check Number
                        </label>
                        <input type="text" id="cheque_number" name="cheque_number"
                            class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700"
                            placeholder="Enter Check number" />
                    </div>

                    <!-- Credit card dropdown (hidden by default) -->
                    <div id="creditCardOptions" class="hidden mt-3 ">
                        <div class="mb-4">
                            <label for="cardOption" class="block text-sm font-medium text-gray-700 mb-1 required">
                                Card Options
                            </label>
                            <select id="cardOption" name="card_option"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                                <option value="" selected>Choose an option</option>
                                <option value="NewCard">New Card</option>

                                @if ($order->customer->cards && $order->customer->cards->count() > 0)
                                    <option value="CardOnFile">Card on File</option>
                                @endif
                            </select>
                        </div>

                        <!-- New Card Fields -->
                        <div id="newCardFields" class="mb-4 hidden">
                            <div class="grid md:grid-cols-2 gap-4">
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="First name" id="firstName" name="firstName"
                                        value=""
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                                </div>
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="Last name" id="lastName" name="lastName"
                                        value=""
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                                </div>
                                <div class="md:col-span-2">
                                    <input type="text" placeholder="Card number" maxlength="19" id="cardNumber"
                                        name="cardNumber" value=""
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                                </div>
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="MM/YY" maxlength="5" id="expiry"
                                        name="expiry" value=""
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                                </div>
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="CVC" maxlength="4" id="cvc"
                                        name="cvc" value=""
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                                </div>
                            </div>
                            <input type="hidden" name="opaqueDataValue" id="opaqueDataValue" />
                            <input type="hidden" name="opaqueDataDescriptor" id="opaqueDataDescriptor" />
                        </div>

                        <!-- Card on File Dropdown -->
                        <div id="cardOnFileDropdown" class="mb-4 hidden">
                            @if ($order->customer->cards && $order->customer->cards->count() > 0)
                                <label class="block text-sm font-medium text-gray-700 mb-1 required">
                                    Select Existing Card
                                </label>
                                <select name="customer_card"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                                    <option value="">Select a saved card</option>
                                    @foreach ($order->customer->cards as $card)
                                        <option value="{{ $card->unique_id }}">{{ $card->card_number }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>

                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible </label>
                        {!! html()->select(
                                'responsible_person',
                                $employees->pluck('full_name', 'id')->prepend('Select Person Responsible', '')->toArray(),
                                old('responsible_person'),
                            )->id('responsible_person')->class([
                                'w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700',
                                'border-red-500' => $errors->has('responsible_person'),
                            ])->required() !!}
                    </div>

                    <!-- Notes (required when Payment Method is "Other") -->
                    <div class="mb-4">
                        <label id="paymentNoteLabel" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        {!! html()->textarea('payment_note', old('payment_note'))->id('payment_note')->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')->rows(3)->placeholder('Enter any additional notes...') !!}
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button"
                    class="close-process-payment-modal-btn px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Save
                </button>
            </div>
            </form>
        </div>
    </div>

    <!-- Void Payment Modal -->
    <div id="voidModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-md shadow-lg flex flex-col">
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Void Payment</h2>
                <button type="button" class="void-modal-close text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <div class="p-6 flex flex-col gap-4">
                <div class="bg-gray-50 rounded-lg p-3 text-sm space-y-1 text-gray-600">
                    <div>Order: <span class="font-medium text-gray-900">{{ $order->order_number }}</span></div>
                    <div>Customer: <span class="font-medium text-gray-900">{{ $order->customer_name }}</span></div>
                    <div>Amount: <span id="void_display_amount" class="font-medium text-gray-900"></span></div>
                </div>

                <!-- Reason -->
                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="void_reason">
                        Reason for Void
                    </label>
                    <select id="void_reason" name="reason" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                        <option value="">Select reason</option>
                        @foreach (\App\Enums\Orders\ProcessedReason::options() as $reasonValue => $reasonLabel)
                            <option value="{{ $reasonValue }}">{{ $reasonLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Other reason (hidden unless "Other") -->
                <div id="void_reason_other_field" class="hidden">
                    <label class="text-sm font-medium text-gray-700 required" for="void_reason_other">
                        Other Reason
                    </label>
                    <input type="text" id="void_reason_other" name="reason_other" maxlength="255"
                        placeholder="Describe the reason"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500" />
                </div>

                <!-- Processed By — the employee physically at the counter -->
                <div class="border-t border-gray-200 pt-4">
                    <p class="text-sm font-semibold text-gray-800 mb-2">Processed By</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm font-medium text-gray-700 required" for="void_processed_by">
                                Processed By
                            </label>
                            <select id="void_processed_by" name="processed_by" required
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                                <option value="">Select employee</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 required" for="void_employee_code">
                                Employee ID
                            </label>
                            <input type="text" id="void_employee_code" name="employee_code" maxlength="20"
                                autocomplete="off" inputmode="numeric" placeholder="Confirm your Employee ID"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500" />
                        </div>
                    </div>
                </div>

                <!-- Disposition — voiding a payment is not the same as
                     cancelling the rental. Default keeps the rental active
                     (void-then-recharge is the common correction workflow);
                     cancellation must be an explicit choice. -->
                <div class="border-t border-gray-200 pt-4">
                    <p class="text-sm font-semibold text-gray-800 mb-2">After the void, this rental should…</p>
                    <div class="space-y-2">
                        <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="radio" name="void_disposition" id="void_disposition_keep" value="keep" checked
                                class="mt-0.5">
                            <span><span class="font-medium">Continue as scheduled</span> — void the payment only
                                (e.g. re-charging the customer). The order stays on Schedule and Dispatch.</span>
                        </label>
                        <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="radio" name="void_disposition" id="void_disposition_cancel" value="cancel"
                                class="mt-0.5">
                            <span><span class="font-medium">Be cancelled</span> — also close the undelivered
                                delivery schedule so the order leaves Schedule and Dispatch. Equipment already
                                delivered stays tracked through return.</span>
                        </label>
                    </div>
                </div>

                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 flex gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-orange-600 flex-shrink-0 mt-0.5" />
                    <div class="text-sm text-orange-800">
                        <p class="font-semibold mb-1">Are you sure you want to void this payment?</p>
                        <p>This will void the full payment transaction through Authorize.net and mark the payment as voided. Voided payments are excluded from sales reports. This action cannot be undone.</p>
                    </div>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" class="void-modal-close px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="button" id="confirmVoidBtn"
                        class="px-4 py-2 text-sm font-medium text-white bg-orange-600 border border-transparent rounded-lg hover:bg-orange-700 disabled:opacity-50">
                        Yes, Void Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Refund source/shortcut eligibility — computed once here so the
         button disabled-states and the JS source table read the same
         server-verified numbers. The backend independently re-verifies
         ALL of this before processing (see RefundPaymentController and
         PaymentAllocationService::validateAllocationSet()) — nothing here
         is trusted on its own; it exists purely to drive the UI. --}}
    @php
        $rfCcFeePercentage = (float) (\App\Helpers\ConfigurationHelper::getSettings('Product Settings', 'credit_card_processing_fee') ?? 0);
        $rfAmbiguous = \App\Services\Orders\PaymentAllocationService::orderHasAmbiguousRefundAttribution($order);
        $rfEligiblePayments = $rfAmbiguous
            ? collect()
            : \App\Services\Orders\PaymentAllocationService::eligibleOriginalPayments($order);

        // One row per eligible original payment — the "Refund Sources"
        // table's entire data source. JS suggests/validates/sums client-side
        // from this array for responsiveness; the server re-derives the
        // identical figures from scratch on submit.
        $rfSources = $rfEligiblePayments->map(function ($payment) use ($rfCcFeePercentage) {
            $isCard = $payment->payment_method === \App\Enums\Orders\OrderPaymentMethod::Card;
            return [
                'id' => $payment->id,
                'method' => \App\Services\PaymentDescriptionPresenter::methodLabel($payment->payment_method),
                'masked' => $isCard && $payment->card_number ? ('•••• ' . $payment->card_number) : null,
                'is_card' => $isCard,
                'has_transaction' => (bool) $payment->transaction_id,
                'date' => optional($payment->payment_datetime)->format('M j, Y'),
                'amount' => (float) $payment->amount,
                'already_refunded' => round((float) $payment->amount - \App\Services\Orders\PaymentAllocationService::remainingRefundable($payment), 2),
                'remaining' => round(\App\Services\Orders\PaymentAllocationService::remainingRefundable($payment), 2),
                'card_fee_capacity' => $isCard && $rfCcFeePercentage > 0
                    ? \App\Services\Orders\PaymentAllocationService::remainingCardFeeCapacity($payment, $rfCcFeePercentage)
                    : 0.0,
            ];
        })->values();

        // Diagnostic breakdown — each narrower than the last — so the
        // disabled-state message names the TRUE reason instead of always
        // blaming "no card payment." A card-paid order can land in any of
        // these buckets (missing gateway transaction, fee % unconfigured,
        // fee capacity already used/too small to round above $0.00) and
        // each needs its own message; collapsing them all into "no card
        // payment was made" is misleading when a real card payment exists.
        $rfCardSources = $rfSources->filter(fn ($s) => $s['is_card']);
        $rfCardSourcesWithTransaction = $rfCardSources->filter(fn ($s) => $s['has_transaction']);
        $rfCcFeeEligibleSources = $rfCardSourcesWithTransaction->filter(fn ($s) => $s['card_fee_capacity'] > 0);

        $rfCcFeeIneligibleReason = null;
        if ($rfAmbiguous) {
            $rfCcFeeIneligibleReason = 'Allocation unavailable for a legacy transaction on this order.';
        } elseif ($rfCardSources->isEmpty()) {
            $rfCcFeeIneligibleReason = 'Only available when at least one original payment was made by Credit / Debit Card.';
        } elseif ($rfCardSourcesWithTransaction->isEmpty()) {
            $rfCcFeeIneligibleReason = 'The original card payment has no recorded gateway transaction on file and cannot be refunded via card automatically.';
        } elseif ($rfCcFeePercentage <= 0) {
            $rfCcFeeIneligibleReason = 'Configure a Credit Card Processing Fee in System Settings to enable this option.';
        } elseif ($rfCcFeeEligibleSources->isEmpty()) {
            $rfCcFeeIneligibleReason = 'No card processing fee capacity remains to retain for this order\'s original card payment(s).';
        } elseif ((float) $order->remaining_amount <= 0) {
            $rfCcFeeIneligibleReason = 'No refundable balance remains.';
        }
        $rfCcFeeEligible = $rfCcFeeIneligibleReason === null;

        // Preview only — "if the employee draws the maximum available from
        // every eligible card source." JS recalculates precisely once
        // actual per-source amounts are set; see calcCcFeePreview().
        $rfCcFeeRetained = 0.0;
        $rfCcFeeRefund = 0.0;
        if ($rfCcFeeEligible) {
            foreach ($rfCcFeeEligibleSources as $s) {
                $draw = round(min($s['remaining'], (float) $order->remaining_amount), 2);
                $fee = round(min($draw * $rfCcFeePercentage / 100, $s['card_fee_capacity']), 2);
                $rfCcFeeRetained += $fee;
                $rfCcFeeRefund += round($draw - $fee, 2);
            }
            $rfCcFeeRetained = round($rfCcFeeRetained, 2);
            $rfCcFeeRefund = round($rfCcFeeRefund, 2);
            if ($rfCcFeeRefund <= 0) {
                $rfCcFeeEligible = false;
                $rfCcFeeIneligibleReason = 'The Credit Card Processing Fee equals or exceeds the refundable amount.';
            }
        }

        // Payment Architecture Finalization — Final Read-Side Cleanup: was a
        // raw, allocation-unaware sum('tax_refunded') across every
        // PartialRefund/Refund row — the one remaining unguarded tax-refund
        // read the Phase 4B audit found. Now sourced from
        // PaymentAllocationService::totalSuccessfulRefundedTax(), the same
        // Allocated-only, multi-source-safe pattern totalSuccessfulRefunded()
        // already uses for the base amount a few lines above.
        $rfAlreadyRefundedTax = \App\Services\Orders\PaymentAllocationService::totalSuccessfulRefundedTax($order);

        // Final Phase — Financial Integrity Audit fix: this used to
        // re-derive "remaining refundable sales tax" with its own inline
        // formula, byte-for-byte identical to the one that already lives in
        // PaymentAllocationService::resolveRequestedTotal()'s Sales Tax
        // Only branch (the same method RefundPaymentController/
        // RefundPaymentPreviewController call). Calling it here instead of
        // re-deriving means there is exactly one place this arithmetic is
        // written.
        [$rfRemainingRefundableTaxResolved, ] = \App\Services\Orders\PaymentAllocationService::resolveRequestedTotal(
            $order, \App\Enums\Orders\RefundCalculationType::SalesTaxOnly, 0.0
        );
        $rfRemainingRefundableTax = round((float) ($rfRemainingRefundableTaxResolved ?? 0), 2);
        $rfTaxOnlyEligible = $rfRemainingRefundableTax > 0 && !$rfAmbiguous;
        $rfTaxOnlyIneligibleReason = $rfAmbiguous
            ? 'Allocation unavailable for a legacy transaction on this order.'
            : ($rfTaxOnlyEligible ? null : 'No refundable sales tax remains for this order.');
    @endphp

    <!-- Refund Modal -->
    <div id="refundModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center"
        data-order-id="{{ $order->order_number }}" data-customer-name="{{ $order->customer_name }}"
        data-original-amount="{{ $order->remaining_amount }}"
        data-order-subtotal="{{ $order->subtotal }}"
        data-order-tax-amount="{{ $order->tax_amount }}"
        data-cc-fee-eligible="{{ $rfCcFeeEligible ? '1' : '0' }}"
        data-cc-fee-percentage="{{ $rfCcFeePercentage }}"
        data-cc-fee-refund="{{ $rfCcFeeRefund }}"
        data-cc-fee-retained="{{ $rfCcFeeRetained }}"
        data-tax-only-eligible="{{ $rfTaxOnlyEligible ? '1' : '0' }}"
        data-tax-original="{{ $order->tax_amount }}"
        data-tax-already-refunded="{{ $rfAlreadyRefundedTax }}"
        data-tax-remaining-refundable="{{ $rfRemainingRefundableTax }}"
        data-ambiguous="{{ $rfAmbiguous ? '1' : '0' }}"
        data-sources='@json($rfSources)'
        data-action="{{ route('admin.order-management.orders.refund-payment', $order->unique_id) }}"
        data-preview-action="{{ route('admin.order-management.orders.refund-payment.preview', $order->unique_id) }}">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 id="refundModalTitle" class="text-lg font-semibold">Process Refund</h2>
                <button type="button"
                    class="close-refund-modal-btn text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>

            <!-- Body -->
            {{ html()->form()->attributes([
                    'id' => 'refundForm',
                    'class' => 'flex-1 flex flex-col justify-between',
                    'parsley-validate' => true,
                ])->open() }}
            @csrf
            {{-- Regenerated fresh each time the modal opens — lets a Store
                 Credit refund recognize a duplicate double-click/network-retry
                 of the same submit without blocking a genuinely new refund. --}}
            <input type="hidden" name="idempotency_token" id="refundIdempotencyToken" value="">

            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                <!-- Order details -->
                <div class="bg-gray-50 rounded-lg p-4 text-sm">
                    <h3 class="font-medium text-gray-900 mb-2">Order Details</h3>
                    <div class="space-y-1 text-gray-600">
                        <div>Order ID:
                            <span class="font-medium text-gray-900" id="rf_order_id">{{ $order->order_number }}</span>
                        </div>
                        <div>Customer:
                            <span class="font-medium text-gray-900"
                                id="rf_customer_name">{{ $order->customer_name }}</span>
                        </div>
                        <div>Original Amount:
                            <span class="font-medium text-gray-900" id="rf_original_amount">
                                {{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}
                            </span>
                        </div>
                        <div>Remaining Amount:
                            <span class="font-medium text-gray-900" id="rf_remaining_amount">
                                {{ \App\Helpers\CustomHelper::formatCurrency($order->remaining_amount) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Phase 3C — outcome of the last submit attempt, and (once
                     a partial/full failure occurs) the recovery banner
                     explaining what's still outstanding. Lives outside both
                     steps so it stays visible whether the employee is
                     reviewing the confirm step or has been sent back to
                     step 1 to reallocate. --}}
                <div id="rf_results_box" class="hidden rounded-lg p-4 border space-y-2 text-sm"></div>
                {{-- Content fully owned by enterRecoveryMode()/exitRecoveryMode() in JS — see below. --}}
                <div id="rf_recovery_banner" class="hidden rounded-lg p-3 bg-amber-50 border border-amber-200 text-sm"></div>

                <!-- STEP 1: FORM -->
                <div id="refundFormStep">
                    <div class="space-y-4">
                        <!-- Amount -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 required" for="refund_amount">
                                Refund Amount
                            </label>

                            <input type="text" id="refund_amount" name="refund_amount" data-digit-input='true'
                                min="0" data-parsley-maxlength="12"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500"
                                value="{{ $order->remaining_amount }}" />
                            <p id="rf_err_amount" class="text-red-500 text-xs mt-1 hidden">
                                Please enter a valid refund amount (max:
                                {{ \App\Helpers\CustomHelper::formatCurrency($order->remaining_amount) }}).
                            </p>

                            <input type="hidden" id="refund_calculation_type" name="refund_calculation_type" value="standard">

                            <!-- Quick buttons -->
                            <div class="mt-2 space-y-2">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">Standard Refunds</p>
                                    <div class="flex gap-2">
                                        <button type="button"
                                            class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 refund-calc-btn"
                                            data-percentage="100">
                                            Full Amount
                                        </button>
                                        <button type="button"
                                            class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 refund-calc-btn"
                                            data-percentage="50">
                                            50%
                                        </button>
                                        <button type="button"
                                            class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 refund-calc-btn"
                                            data-percentage="25">
                                            25%
                                        </button>
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 pt-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">Special Refunds</p>
                                    <div class="flex flex-col gap-2">
                                        <div>
                                            <button type="button" id="rf_cc_fee_btn"
                                                class="px-3 py-1 text-xs rounded refund-special-btn {{ $rfCcFeeEligible ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-gray-50 text-gray-400 cursor-not-allowed' }}"
                                                {{ $rfCcFeeEligible ? '' : 'disabled' }}>
                                                Full Amount Less Card Processing Fee
                                            </button>
                                            <p class="text-[11px] text-gray-400 mt-0.5">
                                                @if ($rfCcFeeEligible)
                                                    Use only for customer cancellations where the company retains the original card-processing expense.
                                                @else
                                                    {{ $rfCcFeeIneligibleReason }}
                                                @endif
                                            </p>
                                        </div>
                                        <div>
                                            <button type="button" id="rf_tax_only_btn"
                                                class="px-3 py-1 text-xs rounded refund-special-btn {{ $rfTaxOnlyEligible ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-gray-50 text-gray-400 cursor-not-allowed' }}"
                                                {{ $rfTaxOnlyEligible ? '' : 'disabled' }}>
                                                Sales Tax Only
                                            </button>
                                            <p class="text-[11px] text-gray-400 mt-0.5">
                                                @if ($rfTaxOnlyEligible)
                                                    Use only when correcting an incorrect sales-tax charge, such as for a tax-exempt customer.
                                                @else
                                                    {{ $rfTaxOnlyIneligibleReason }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic breakdown -->
                            <div id="rf_breakdown_box" class="hidden mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm space-y-1">
                                <p id="rf_breakdown_title" class="font-medium text-gray-900 mb-1"></p>

                                <div id="rf_breakdown_standard" class="hidden space-y-1">
                                    <div class="flex justify-between"><span class="text-gray-600">Purchase portion:</span><span id="rf_bd_purchase" class="font-medium text-gray-900">$0.00</span></div>
                                    <div class="flex justify-between"><span class="text-gray-600">Sales tax portion:</span><span id="rf_bd_tax" class="font-medium text-gray-900">$0.00</span></div>
                                    <div class="flex justify-between border-t border-gray-200 pt-1"><span class="text-gray-600">Total refund:</span><span id="rf_bd_total" class="font-semibold text-gray-900">$0.00</span></div>
                                    @if ((float) $order->tax_amount > 0)
                                        <p class="text-[11px] text-gray-400 pt-1">When sales tax was charged, a standard partial refund is divided proportionally between the purchase amount and sales tax at the same rate as the original order.</p>
                                    @endif
                                </div>

                                <div id="rf_breakdown_cc_fee" class="hidden space-y-1">
                                    <div class="flex justify-between"><span class="text-gray-600">Refundable amount:</span><span id="rf_bd_cc_refundable" class="font-medium text-gray-900">$0.00</span></div>
                                    <div class="flex justify-between"><span class="text-gray-600" id="rf_bd_cc_fee_label">Card processing fee:</span><span id="rf_bd_cc_fee" class="font-medium text-red-700">$0.00</span></div>
                                    <div class="flex justify-between border-t border-gray-200 pt-1"><span class="text-gray-600">Customer refund:</span><span id="rf_bd_cc_total" class="font-semibold text-gray-900">$0.00</span></div>
                                </div>

                                <div id="rf_breakdown_tax_only" class="hidden space-y-1">
                                    <div class="flex justify-between"><span class="text-gray-600">Sales tax originally charged:</span><span id="rf_bd_tax_original" class="font-medium text-gray-900">$0.00</span></div>
                                    <div class="flex justify-between"><span class="text-gray-600">Sales tax previously refunded:</span><span id="rf_bd_tax_prev" class="font-medium text-gray-900">$0.00</span></div>
                                    <div class="flex justify-between border-t border-gray-200 pt-1"><span class="text-gray-600">Sales tax refund now:</span><span id="rf_bd_tax_now" class="font-semibold text-gray-900">$0.00</span></div>
                                </div>
                            </div>
                        </div>

                        {{-- Phase 3C — Refund Sources. Hidden entirely for a
                             single-payment order (one source is preselected
                             and fully allocated with no employee input
                             needed — see renderSources() in the JS below);
                             shown as an editable table only once the order
                             has 2+ eligible original payments. --}}
                        <div id="rf_sources_box" class="hidden border-t border-gray-200 pt-4">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm font-semibold text-gray-800">Refund Sources</p>
                                <span id="rf_sources_total" class="text-xs font-medium text-gray-500">Allocated: $0.00 of $0.00</span>
                            </div>
                            <div id="rf_sources_list" class="space-y-2"></div>
                            <p id="rf_sources_error" class="text-red-500 text-xs mt-2 hidden"></p>
                        </div>

                        <div id="rf_ambiguous_box" class="hidden rounded-lg p-3 bg-amber-50 border border-amber-200">
                            <p class="text-sm font-medium text-amber-800">Refund unavailable</p>
                            <p class="text-xs text-amber-700 mt-1">
                                This order has a legacy refund whose original payment cannot be determined
                                ("Allocation unavailable for legacy transaction"). New refunds are blocked
                                until that transaction is resolved.
                            </p>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 required" for="refund_reason">
                                Reason for Refund
                            </label>
                            <select id="refund_reason" name="reason" required
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 focus:ring focus:border-blue-500">
                                <option value="">Select reason</option>
                                @foreach (\App\Enums\Orders\ProcessedReason::options() as $reasonValue => $reasonLabel)
                                    <option value="{{ $reasonValue }}">{{ $reasonLabel }}</option>
                                @endforeach
                            </select>
                            <p id="rf_err_reason" class="text-red-500 text-xs mt-1 hidden">
                                Please select a reason for the refund.
                            </p>
                        </div>

                        <!-- Other reason (hidden unless "Other") -->
                        <div id="refund_reason_other_field" class="hidden">
                            <label class="text-sm font-medium text-gray-700 required" for="refund_reason_other">
                                Other Reason
                            </label>
                            <input type="text" id="refund_reason_other" name="reason_other" maxlength="255"
                                placeholder="Describe the reason"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500" />
                        </div>

                        <!-- Payment Type -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 required" for="refund_payment_type">
                                Payment Type
                            </label>
                            @php
                                // Credit/Debit Card is only offered when Card was actually
                                // one of the original payment methods — every other
                                // canonical method comes straight from
                                // PaymentMethod::options(), never a second hand-typed
                                // copy of its labels.
                                //
                                // Tier 2 fix: previously checked only
                                // Order::last_payment_type (the single most recent
                                // payment row), so a Cash+Card split-payment order
                                // would hide this option whenever Card wasn't the LAST
                                // row entered. Now checks every settled original
                                // payment on the order via the canonical summary.
                                $refundOptions = \App\Enums\Customers\PaymentMethod::options();

                                if (!\App\Services\Orders\OrderPaymentSummary::for($order)->paymentMethodsUsed->contains(\App\Enums\Orders\OrderPaymentMethod::Card)) {
                                    unset($refundOptions[\App\Enums\Customers\PaymentMethod::CreditCard->value]);
                                }
                            @endphp
                            {!! html()->select('payment_type', $refundOptions)->id('refund_payment_type')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->required() !!}
                            <small class="text-gray-500 mt-1 block">Note: Credit/Debit Card option is only available if the
                                initial payment was made by Credit/Debit Card.</small>
                        </div>

                        <!-- Cheque Number (hidden by default) -->
                        <div id="refund_cheque_number_field" class="hidden">
                            <label class="text-sm font-medium text-gray-700" for="cheque_number">
                                Check Number
                            </label>
                            <input type="text" id="refund_cheque_number" name="cheque_number"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700"
                                placeholder="Enter Check number" />
                        </div>

                        <!-- Other — description required (hidden by default) -->
                        <div id="refund_payment_note_field" class="hidden">
                            <label class="text-sm font-medium text-gray-700 required" for="refund_payment_note">
                                Describe Payment Method
                            </label>
                            <input type="text" id="refund_payment_note" name="payment_note" maxlength="255"
                                placeholder="E.g. Manufacturer Credit, Trade Credit"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" />
                        </div>

                        <!-- Type indicator -->
                        <div id="rf_type_box" class="hidden rounded-lg p-3">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-information-circle', 'w-6 h-6 inline-block rounded-full', ['id' => 'rf_type_icon'])
                                <p id="rf_type_label" class="text-sm font-medium"></p>
                            </div>
                            <p id="rf_type_text" class="text-xs mt-1"></p>
                        </div>

                        <!-- Processed By — the employee physically at the counter -->
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-sm font-semibold text-gray-800 mb-2">Processed By</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 required" for="refund_processed_by">
                                        Processed By
                                    </label>
                                    <select id="refund_processed_by" name="processed_by" required
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                                        <option value="">Select employee</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 required" for="refund_employee_code">
                                        Employee ID
                                    </label>
                                    <input type="text" id="refund_employee_code" name="employee_code" maxlength="20"
                                        autocomplete="off" inputmode="numeric" placeholder="Confirm your Employee ID"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500" />
                                </div>
                            </div>
                            <p id="rf_err_processed_by" class="text-red-500 text-xs mt-1 hidden">
                                Select the employee and enter their Employee ID.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: CONFIRM -->
                <div id="refundConfirmStep" class="hidden space-y-4">
                    <div class="text-center mb-2">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <!-- simple alert icon -->
                            <svg class="text-red-600 w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                <line x1="12" y1="9" x2="12" y2="13" stroke-width="2" />
                                <line x1="12" y1="17" x2="12.01" y2="17" stroke-width="2" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">Confirm Refund</h3>
                        <p class="text-gray-600 text-sm">Please review the refund details before processing.</p>
                    </div>

                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm">
                        <div class="space-y-2">
                            <div id="rf_c_calc_type_row" class="flex justify-between hidden">
                                <span class="text-gray-600">Refund Type:</span>
                                <span id="rf_c_calc_type" class="font-medium text-gray-900">—</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Refund Total:</span>
                                <span id="rf_c_amount" class="font-semibold text-red-800">$0.00</span>
                            </div>

                            {{-- Phase 3C — only shown for a multi-source refund. --}}
                            <div id="rf_c_sources_box" class="hidden border-t border-red-200 pt-2 space-y-1">
                                <span class="text-gray-600 block mb-1">Sources:</span>
                                <div id="rf_c_sources_list" class="space-y-1"></div>
                            </div>

                            <div id="rf_c_tax_row" class="flex justify-between hidden">
                                <span class="text-gray-600">Sales Tax Portion:</span>
                                <span id="rf_c_tax" class="font-medium text-gray-900">$0.00</span>
                            </div>
                            <div id="rf_c_purchase_row" class="flex justify-between hidden">
                                <span class="text-gray-600">Purchase Portion:</span>
                                <span id="rf_c_purchase" class="font-medium text-gray-900">$0.00</span>
                            </div>
                            <div id="rf_c_fee_row" class="flex justify-between hidden">
                                <span class="text-gray-600">Card Processing Fee Retained:</span>
                                <span id="rf_c_fee" class="font-medium text-red-700">$0.00</span>
                            </div>
                            <div id="rf_c_net_row" class="flex justify-between hidden">
                                <span class="text-gray-600">Customer Refund:</span>
                                <span id="rf_c_net" class="font-semibold text-gray-900">$0.00</span>
                            </div>

                            <div class="flex justify-between items-start">
                                <span class="text-gray-600">Reason:</span>
                                <span id="rf_c_reason"
                                    class="font-medium text-gray-900 max-w-[220px] break-words whitespace-pre-line">—</span>
                            </div>
                            <div id="rf_c_remaining_row" class="flex justify-between hidden">
                                <span class="text-gray-600">Remaining Balance:</span>
                                <span id="rf_c_remaining" class="font-semibold text-gray-900">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm font-medium text-yellow-800">Important Notice</p>
                        <p class="text-xs text-yellow-700 mt-1">
                            This action cannot be undone. The refund will be processed immediately and the customer will be
                            notified via email.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <div id="refundFormStepBtnsDiv">
                    <button type="button"
                        class="close-refund-modal-btn px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </button>

                    <!-- Step 1 buttons -->
                    <button type="button" id="rf_btn_initiate"
                        class="px-6 py-2 rounded-md bg-red-600 text-white font-medium hover:bg-red-700 shadow-sm transition">
                        Initiate Refund
                    </button>
                </div>


                <!-- Step 2 buttons -->
                <div id="refundConfirmStepBtnsDiv" class="hidden">
                    <button type="button" id="rf_btn_back"
                        class="px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                        Back
                    </button>
                    <button type="submit" id="rf_btn_confirm"
                        class="px-6 py-2 rounded-md bg-red-600 text-white font-medium hover:bg-red-700 shadow-sm transition inline-flex items-center gap-2">
                        Process Refund
                    </button>
                </div>
            </div>
            {{ html()->form()->close() }}
        </div>
    </div>

    {{-- Payment Details Modal — read-only, opened from the "Payment Details"
         button next to the header status pills. Everything not needed at a
         glance (reference numbers, notes, Store Credit breakdown) lives
         here instead of cluttering the header. --}}
    @php
        $pdPayment = $order->lastPaidPayment ?? $order->lastPayment;
        $pdStoreCreditEntry = null;
        if ($pdPayment && $pdPayment->payment_method === \App\Enums\Orders\OrderPaymentMethod::StoreCredit) {
            $pdStoreCreditEntry = \App\Models\Customers\CustomerCredit::where('order_id', $order->id)
                ->where('type', \App\Services\CustomerCreditService::TYPE_REDEMPTION)
                ->latest('id')
                ->first();
        }
    @endphp
    <div id="paymentDetailsModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-lg font-semibold">Payment Details</h2>
                <button type="button"
                    class="close-payment-details-modal-btn text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>

            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                @if ($pdPayment)
                    @php
                        // Tier 2: Status/Method here must describe the ORDER
                        // (matching "Amount Paid" and "Remaining Balance"
                        // right below, which are already order-level
                        // aggregates), not just this one $pdPayment row —
                        // otherwise a split-payment order shows one method
                        // next to a total that reflects every method.
                        $pdSummary = \App\Services\Orders\OrderPaymentSummary::for($order);
                    @endphp
                    <div class="bg-gray-50 rounded-lg p-4 text-sm space-y-1 text-gray-600">
                        <div class="flex justify-between">
                            <span>Status:</span>
                            {!! \App\Helpers\CustomHelper::orderPaymentStatusBadge($pdSummary) !!}
                        </div>
                        <div class="flex justify-between">
                            <span>Method:</span>
                            {{-- methodsUsedForDisplay() surfaces "Account" for an
                                 on-account order (whose settled-method set is empty)
                                 instead of "Unknown". --}}
                            <span class="font-medium text-gray-900">{{ \App\Services\PaymentDescriptionPresenter::methodsUsedLabel($pdSummary->methodsUsedForDisplay()) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Date:</span>
                            <span class="font-medium text-gray-900">{{ \App\Helpers\CustomHelper::formatDateTime($pdPayment->payment_datetime) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Employee:</span>
                            <span class="font-medium text-gray-900">{{ $pdPayment->createdBy?->full_name ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Amount Paid:</span>
                            <span class="font-medium text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($order->total_paid) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-1">
                            <span>Remaining Balance:</span>
                            <span class="font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($order->balance_due) }}</span>
                        </div>
                    </div>

                    @php
                        $pdReferenceLabel = match (true) {
                            $pdPayment->payment_method === \App\Enums\Orders\OrderPaymentMethod::Card => 'Card / Authorization',
                            $pdPayment->payment_method === \App\Enums\Orders\OrderPaymentMethod::Cheque => 'Check Number',
                            default => null,
                        };
                    @endphp

                    @if ($pdReferenceLabel)
                        <div class="bg-white border border-gray-200 rounded-lg p-3 text-sm">
                            <div class="text-[11px] uppercase tracking-wide text-gray-400 mb-1">{{ $pdReferenceLabel }}</div>
                            @if ($pdPayment->payment_method === \App\Enums\Orders\OrderPaymentMethod::Card)
                                <div class="text-gray-800">
                                    @if ($pdPayment->card_number) •••• {{ $pdPayment->card_number }} @endif
                                    @if ($pdPayment->auth_code) &nbsp;·&nbsp;Auth {{ $pdPayment->auth_code }} @endif
                                    @if ($pdPayment->transaction_id) &nbsp;·&nbsp;Txn {{ $pdPayment->transaction_id }} @endif
                                </div>
                            @else
                                <div class="text-gray-800">#{{ $pdPayment->cheque_number }}</div>
                            @endif
                        </div>
                    @endif

                    @if ($pdPayment->payment_note)
                        <div class="bg-white border border-gray-200 rounded-lg p-3 text-sm">
                            <div class="text-[11px] uppercase tracking-wide text-gray-400 mb-1">Note</div>
                            <div class="text-gray-800">{{ $pdPayment->payment_note }}</div>
                        </div>
                    @endif

                    @if ($pdStoreCreditEntry)
                        @php
                            $pdBeginningCredit = \App\Services\CustomerCreditService::balanceBefore($pdStoreCreditEntry);
                            $pdAppliedCredit = (float) $pdStoreCreditEntry->amount;
                            $pdRemainingCredit = round($pdBeginningCredit - $pdAppliedCredit, 2);
                        @endphp
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm space-y-1">
                            <div class="font-medium text-gray-900 mb-1">Store Credit Applied</div>
                            <div class="flex justify-between"><span>Beginning Credit:</span><span class="font-medium text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($pdBeginningCredit) }}</span></div>
                            <div class="flex justify-between"><span>Applied:</span><span class="font-medium text-red-700">-{{ \App\Helpers\CustomHelper::formatCurrency($pdAppliedCredit) }}</span></div>
                            <div class="flex justify-between border-t border-blue-200 pt-1"><span>Remaining Credit:</span><span class="font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($pdRemainingCredit) }}</span></div>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-500">No payment recorded for this order yet.</p>
                @endif
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button"
                    class="close-payment-details-modal-btn px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Media Grid Modal -->
    <div id="allMediaModal"
        class="fixed inset-0 z-[99999] hidden bg-black/70 backdrop-blur-sm flex justify-center items-center transition-opacity duration-300 px-4">

        <div class="relative bg-white rounded-2xl shadow-xl max-w-6xl w-full mx-4 overflow-hidden">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">All Media</h2>
                <button type="button" onclick="closeAllMedia()"
                    class="text-2xl text-gray-500 hover:text-gray-800 leading-none">&times;</button>
            </div>

            <!-- Body -->
            <div id="allMediaContent"
                class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[70vh] overflow-y-auto bg-gray-50">
                <!-- Media items injected here -->
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="closeAllMedia()"
                    class="px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="checklistModal"
        class="fixed inset-0 z-[99999] hidden bg-black/70 backdrop-blur-sm flex transition-opacity duration-300"
        aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-scrollable w-full mx-auto">
            <div class="relative mx-auto my-10 px-4 w-[95vw] max-w-5xl">
                <div class="bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-4 sm:px-6 py-3 border-b">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800">
                            <span id="checklistProductTitle"></span> Checklist
                        </h3>
                        <button type="button" class="text-2xl text-gray-500 leading-none focus:outline-none"
                            onclick="closeChecklistModal()" aria-label="Close">&times;</button>
                    </div>

                    <div class=" overflow-y-auto max-h-[70vh]">
                        <!-- Content -->
                        <div class="px-2 sm:px-4 py-3 modal-scrollable w-full mx-auto">
                            <div class="bg-white border border-gray-200 rounded-md overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm whitespace-nowrap">
                                    <thead class="bg-gray-100 text-gray-600">

                                        <tr class="text-left text-gray-600 border-b">
                                            <th class="py-2 px-2">Checklist Item</th>
                                            <th class="py-2 px-2">Delivered</th>
                                            <th class="py-2 px-2">Returned</th>
                                            <!-- <th class="py-2 px-2">Balance</th>
                                                                                <th class="py-2 px-2">Value</th> -->
                                            <th class="py-2 px-2 text-right">Customer Owes</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-800">

                                        @php
                                            $checklistTotal = 0;
                                            $hourTrackingTotal = 0;

                                        @endphp

                                        @foreach ($order->products as $product)
                                            @foreach ($product->checklistQuestions()->indexOrder()->get() as $question)
                                                @php
                                                    // Get the last valid answer (delivery or return != 0), sorted by index_number
                                                    $valid = $question->answers
                                                        ->filter(
                                                            fn($a) => $a->is_delivery_answer == 1 ||
                                                                $a->is_return_answer == 1,
                                                        )
                                                        ->sortByDesc('index_number')
                                                        ->first();

                                                    // If no valid answer, fallback to the last entry (even if 0/0)
                                                    $latest =
                                                        $valid ??
                                                        $question->answers->sortByDesc('index_number')->first();

                                                    $isDeliveryOverride = !(
                                                        $question->deliverySelectedAnswer &&
                                                        $question->deliverySelectedAnswer->is_delivery_answer == 1
                                                    );

                                                    $rowAmount = 0;
                                                    if (!$isDeliveryOverride && $latest && $latest->is_return_answer == 1 && $latest->is_delivery_answer == 0) {
                                                        $deliveryAmount = $question->deliverySelectedAnswer->delivery_amount ?? 0;
                                                        $returnAmount = $latest->user_return_amount ?? ($latest->return_amount ?? 0);
                                                        $rowAmount = max($returnAmount - $deliveryAmount, 0);
                                                    }
                                                    $checklistTotal += $rowAmount;
                                                @endphp
                                                <tr class="border-b checklist-row" data-product-id="{{ $product->id }}" data-amount="{{ $rowAmount }}">
                                                    <!-- Checklist Item -->
                                                    <td class="py-2 px-2">{{ $question->question_name }}</td>

                                                    <!-- Delivered -->
                                                    <td class="py-2 px-2 align-top">

                                                        @if ($question->deliverySelectedAnswer && $question->deliverySelectedAnswer->is_delivery_answer == 1)
                                                            <div class="max-w-[200px] whitespace-normal break-words">
                                                                {{ $question->deliverySelectedAnswer->delivery_answer }}
                                                            </div>
                                                        @else
                                                            <span class="inline-block  min-w-10 text-red-600">
                                                                Admin Override
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <!-- Returned -->
                                                    <td class="py-2 px-2 align-top">
                                                        @if ($question->returnSelectedAnswer && $question->returnSelectedAnswer->is_return_answer == 1)
                                                            <div class="max-w-[200px] whitespace-normal break-words">
                                                                {{ $question->returnSelectedAnswer->return_answer }}
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td class="py-2 px-2 text-right">
                                                        @if ($latest)
                                                            {{-- Case 1: Both selected --}}
                                                            @if ($latest->is_delivery_answer == 1 && $latest->is_return_answer == 1)
                                                                <span
                                                                    class="inline-block border-b border-gray-300 min-w-10 text-gray-600">$0.00</span>

                                                                {{-- Case 2: Delivery only --}}
                                                            @elseif($latest->is_delivery_answer == 1 && $latest->is_return_answer == 0)
                                                                <span
                                                                    class="inline-block border-b border-gray-300 min-w-10 text-gray-600">
                                                                    <!-- ${{ $latest->user_delivery_amount ?? ($latest->delivery_amount ?? 0) }} -->
                                                                    $0
                                                                </span>
                                                                {{-- Case 3: Return only --}}
                                                            @elseif($latest->is_return_answer == 1 && $latest->is_delivery_answer == 0)
                                                                <span
                                                                    class="inline-block border-b border-gray-300 min-w-10 text-red-600">
                                                                    ${{ $rowAmount }}
                                                                </span>
                                                                {{-- Case 4: Nothing selected --}}
                                                            @else
                                                                <span
                                                                    class="inline-block border-b border-gray-300 min-w-10 text-gray-400">$0.00</span>
                                                            @endif
                                                        @else
                                                            <span
                                                                class="inline-block border-b border-gray-300 min-w-10 text-gray-400">$0.00</span>
                                                        @endif
                                                    </td>


                                                </tr>
                                            @endforeach
                                        @endforeach

                                        @php
                                            $damageBaseTotal = 0;
                                            $damageAdjustmentTotal = 0;
                                        @endphp
                                        @foreach ($order->products as $product)
                                            @php
                                                $productDamageBase = (float) ($product->damage_charge ?? 0);
                                                $productDamageAdjustment = $product->damageChargeLogs?->sum('change_amount') ?? 0;
                                                $productFinalDamage = max(0, $productDamageBase + $productDamageAdjustment);

                                                $damageBaseTotal += $productDamageBase;
                                                $damageAdjustmentTotal += $productDamageAdjustment;
                                            @endphp
                                            @if ($productDamageBase > 0 || $productDamageAdjustment != 0)
                                                {{-- DAMAGE SUMMARY ROW --}}
                                                <tr class=" border-b damage-row" data-product-id="{{ $product->id }}" data-amount="{{ $productFinalDamage }}">
                                                    <td class="py-2 px-2 ">
                                                        Damage Amount Initialized
                                                    </td>

                                                    <td class="py-2 px-2 ">
                                                        Base Amount (${{ number_format($productDamageBase, 2) }})
                                                    </td>

                                                    <td
                                                        class="py-2 px-2  {{ $productDamageAdjustment < 0 ? 'text-red-600' : 'text-gray-600' }}">
                                                        Adjust Amount (
                                                        {{ $productDamageAdjustment >= 0 ? '+' : '-' }}
                                                        ${{ number_format(abs($productDamageAdjustment), 2) }} )
                                                    </td>

                                                    <td class="py-2 px-2 text-right text-gray-800">
                                                        ${{ number_format($productFinalDamage, 2) }}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach

                                        @php
                                            $fuelBaseTotal = 0;
                                            $fuelAdjustmentTotal = 0;

                                            $arrFuelDelivery = [
                                                ['id' => 10, 'name' => 'Prepaid'],
                                                ['id' => 9, 'name' => 'Full'],
                                                ['id' => 8, 'name' => '7/8'],
                                                ['id' => 7, 'name' => '3/4'],
                                                ['id' => 6, 'name' => '5/8'],
                                                ['id' => 5, 'name' => '1/2'],
                                                ['id' => 4, 'name' => '3/8'],
                                                ['id' => 3, 'name' => '1/4'],
                                                ['id' => 2, 'name' => '1/8'],
                                                ['id' => 1, 'name' => 'Empty'],
                                            ];
                                            $fuelMap = collect($arrFuelDelivery)->pluck('name', 'id');
                                        @endphp
                                        @foreach ($order->products as $product)
                                            @if (!is_null($product->fuel_initial_reading) || !is_null($product->fuel_final_reading) || !is_null($product->fuel_total_charge))
                                                @php
                                                    $productFuelBase = (float) ($product->fuel_total_charge ?? 0);
                                                    $productFuelAdjustment = $product->fuelChargeLogs?->sum('change_amount') ?? 0;
                                                    $productFinalFuel = max(0, $productFuelBase + $productFuelAdjustment);

                                                    $fuelBaseTotal += $productFuelBase;
                                                    $fuelAdjustmentTotal += $productFuelAdjustment;

                                                    $initialFuel = $fuelMap[$product->fuel_initial_reading ?? null] ?? '-';
                                                    $finalFuel = $fuelMap[$product->fuel_final_reading ?? null] ?? '-';

                                                    $hasPrepaidFuelOption = !is_null(
                                                        data_get($product->product_data, 'product_rental_items_prices.rental_prepaid_fuel'),
                                                    );
                                                @endphp

                                                <tr class="border-b fuel-row" data-product-id="{{ $product->id }}" data-amount="{{ $productFinalFuel }}">
                                                    <td class="py-2 px-2">
                                                        Fuel (
                                                        {{ $product->equipment?->power_source_type
                                                            ? $product->equipment->power_source_type->label()
                                                            : 'Select Power Source' }})
                                                        @if ($product->fuel_initial_reading == 10 || $hasPrepaidFuelOption)
                                                            <span class="ml-1 inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded bg-green-100 text-green-700">Prepaid</span>
                                                        @endif
                                                    </td>

                                                    <td class="py-2 px-2">
                                                        @if (is_null($product->fuel_initial_reading))
                                                            <span class="inline-block min-w-10 text-red-600">
                                                                Admin Override
                                                            </span>
                                                        @else
                                                            {{ $initialFuel }}
                                                        @endif
                                                    </td>

                                                    <td
                                                        class="py-2 px-2 {{ $productFuelAdjustment < 0 ? 'text-red-600' : 'text-gray-800' }}">
                                                        {{ $finalFuel }}
                                                    </td>

                                                    <td class="py-2 px-2 text-right text-gray-800">
                                                        ${{ number_format($productFinalFuel, 2) }}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach

                                        @php
                                            $finalDamageTotal = max(0, $damageBaseTotal + $damageAdjustmentTotal);
                                            $finalFuelTotal = max(0, $fuelBaseTotal + $fuelAdjustmentTotal);
                                        @endphp



                                    </tbody>
                                </table>
                            </div>




                            {{-- Second Table: Hour Tracking --}}
                            <div class="bg-white border border-gray-200 rounded-md overflow-x-auto mt-6">
                                <table class="min-w-full divide-y divide-gray-200 text-sm whitespace-nowrap">
                                    <thead class="bg-gray-100 text-gray-600">
                                        <tr class="text-left text-gray-600 border-b">
                                            <th class="py-2 px-2">Product</th>
                                            <th class="py-2 px-2">Start Hour</th>
                                            <th class="py-2 px-2">End Hour</th>
                                            <th class="py-2 px-2">Allocated</th>
                                            <th class="py-2 px-2">Additional</th>
                                            <th class="py-2 px-2">Rate</th>
                                            <th class="py-2 px-2 text-right">Charge</th>
                                            <th class="py-2 px-2 text-right w-20">Customer Owes</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-800">
                                        @foreach ($order->products as $product)
                                            @if (($product->equipment?->is_tracked ?? 'No') === 'Yes')
                                                @php
                                                    $productHasDeliveryOverride = $product
                                                        ->checklistQuestions()
                                                        ->get()
                                                        ->contains(
                                                            fn($q) => !(
                                                                $q->deliverySelectedAnswer &&
                                                                $q->deliverySelectedAnswer->is_delivery_answer == 1
                                                            ),
                                                        );

                                                    $endHoursOverride = is_null($product->end_hours) || $productHasDeliveryOverride;

                                                    $startHours = (float) $product->start_hours;
                                                    $endHours = (float) $product->end_hours;
                                                    $allocatedHours = (float) $product->allocated_hours;
                                                    $hourRate = (float) ($product->equipment?->overage_rate ?? 0);

                                                    if ($endHoursOverride) {
                                                        $additionalHours = 0;
                                                        $charge = 0;
                                                        $totalAmount = 0;
                                                    } else {
                                                        $usedHours = max(0, $endHours - $startHours);
                                                        $additionalHours = max(0, ceil($usedHours - $allocatedHours));

                                                        $charge = $additionalHours * $hourRate;
                                                        $totalAmount = $charge;
                                                    }

                                                    $hourTrackingTotal += $totalAmount;

                                                @endphp

                                                <tr class="border-b hour-row" data-product-id="{{ $product->id }}" data-amount="{{ $totalAmount }}">
                                                    <td class="py-2 px-2">{{ $product->product_name }}</td>
                                                    <td class="py-2 px-2">{{ $startHours }}</td>
                                                    <td class="py-2 px-2">
                                                        @if ($endHoursOverride)
                                                            <span class="inline-block min-w-10 text-red-600">
                                                                Admin Override
                                                            </span>
                                                        @else
                                                            {{ $endHours }}
                                                        @endif
                                                    </td>
                                                    <td class="py-2 px-2">{{ $allocatedHours }} h</td>
                                                    <td class="py-2 px-2">{{ $additionalHours }} h</td>
                                                    <td class="py-2 px-2">${{ number_format($hourRate, 2) }}</td>
                                                    <td class="py-2 px-2 text-right text-right">
                                                        ${{ number_format($charge, 2) }}</td>
                                                    <td class="py-2 px-2 font-semibold text-right">
                                                        ${{ number_format($totalAmount, 2) }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>

                                    <tfoot class="bg-gray-50 text-gray-800">

                                        <tr>
                                            <td colspan="7" class="py-2 px-2 text-right">
                                                Checklist Total:
                                            </td>
                                            <td class="py-2 px-2 text-right" id="checklistTotalDisplay" data-all-amount="{{ number_format($checklistTotal, 2) }}">
                                                ${{ number_format($checklistTotal, 2) }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td colspan="7" class="py-2 px-2 text-right">
                                                Hour Tracking Total:
                                            </td>
                                            <td class="py-2 px-2 text-right" id="hourTrackingTotalDisplay" data-all-amount="{{ number_format($hourTrackingTotal, 2) }}">
                                                ${{ number_format($hourTrackingTotal, 2) }}
                                            </td>
                                        </tr>
                                        <tr id="damageTotalRow" class="{{ $damageBaseTotal > 0 || $damageAdjustmentTotal != 0 ? '' : 'hidden' }}">
                                            <td colspan="7" class="py-2 px-2 text-right ">
                                                Final Damage Charge:
                                            </td>
                                            <td class="py-2 px-2 text-right " id="damageTotalDisplay" data-all-amount="{{ number_format($finalDamageTotal, 2) }}">
                                                ${{ number_format($finalDamageTotal, 2) }}
                                            </td>
                                        </tr>

                                        <tr id="fuelTotalRow" class="{{ $fuelBaseTotal > 0 || $fuelAdjustmentTotal != 0 ? '' : 'hidden' }}">
                                            <td colspan="7" class="py-2 px-2 text-right">
                                                Final Fuel Charge:
                                            </td>
                                            <td class="py-2 px-2 text-right" id="fuelTotalDisplay" data-all-amount="{{ number_format($finalFuelTotal, 2) }}">
                                                ${{ number_format($finalFuelTotal, 2) }}
                                            </td>
                                        </tr>


                                        <tr class="font-bold border-t">
                                            <td colspan="7" class="py-3 px-2 text-right">
                                                Grand Total:
                                            </td>
                                            <td class="py-3 px-2 text-right text-gray-900" id="grandTotalDisplay" data-all-amount="{{ number_format($checklistTotal + $hourTrackingTotal + $finalDamageTotal + $finalFuelTotal, 2) }}">
                                                {{ number_format($checklistTotal + $hourTrackingTotal + $finalDamageTotal + $finalFuelTotal, 2) }}
                                            </td>
                                        </tr>

                                    </tfoot>





                                </table>
                            </div>

                            @foreach ($order->products as $orderProduct)
                                @if (!empty($orderProduct->product))
                                    <div class=" py-2 checklist-notes-panel" data-product-id="{{ $orderProduct->id }}">
                                        <form class="bg-white">
                                            <!-- 2-column grid (1 column on mobile) -->
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                                <!-- LEFT COLUMN -->
                                                <div class="flex flex-col gap-4">
                                                    <!-- Delivery Notes -->
                                                    <div>
                                                        <label for="delivery_notes"
                                                            class="block text-sm text-gray-700 mb-1">Delivery Notes</label>
                                                        <div
                                                            class="text-sm text-gray-700 border border-gray-200 shadow-sm rounded-lg p-4">
                                                            {{ $orderProduct->delivery_notes ?? '—' }}</div>
                                                    </div>

                                                    <!-- Delivery Employee -->
                                                    <div>
                                                        <label for="delivery_employee"
                                                            class="block text-sm text-gray-700 mb-1">Delivery
                                                            Employee</label>
                                                        <div
                                                            class="w-full md:w-56 border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
                                                            {{ $orderProduct->deliveryEmployee->full_name ?? '—' }}

                                                        </div>
                                                    </div>

                                                    <!-- Delivery Signature (Photo) -->
                                                    <div>
                                                        <label class="block text-sm text-gray-700 mb-1">Delivery
                                                            Signature</label>
                                                        @if (!empty($orderProduct->deliverySignatureMedia))
                                                            <img src="{{ optional($orderProduct->deliverySignatureMedia)->url }}"
                                                                class="w-50 border rounded shadow-sm" alt="Signature">
                                                        @else
                                                            <p class="text-gray-500 italic">No signature available</p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- RIGHT COLUMN -->
                                                <div class="flex flex-col gap-4">
                                                    <!-- Returned Notes -->
                                                    <div>
                                                        <label for="returned_notes"
                                                            class="block text-sm text-gray-700 mb-1">Returned Notes</label>
                                                        <div
                                                            class="text-sm text-gray-700 border border-gray-200 shadow-sm rounded-lg p-4">
                                                            {{ $orderProduct->pickup_notes ?? '—' }}</div>
                                                    </div>

                                                    <!-- Returned Employee -->
                                                    <div>
                                                        <label for="returned_employee"
                                                            class="block text-sm text-gray-700 mb-1">Returned
                                                            Employee</label>
                                                        <div
                                                            class="w-full md:w-56 border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
                                                            {{ $orderProduct->pickupEmployee->full_name ?? '—' }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm text-gray-700 mb-1">Returned
                                                            Signature</label>
                                                        @if (!empty($orderProduct->returnSignatureMedia))
                                                            <img src="{{ optional($orderProduct->returnSignatureMedia)->url }}"
                                                                class="w-50 border rounded shadow-sm" alt="Signature">
                                                        @else
                                                            <p class="text-gray-500 italic">No signature available</p>
                                                        @endif
                                                    </div>

                                                    <!-- Save button pinned to bottom on md+ -->
                                                    <!-- <div class="mt-auto flex justify-end">
                                                                                            <button type="submit"
                                                                                                class="px-4 py-2 text-sm rounded bg-sky-600 text-white hover:bg-sky-700 shadow-sm">
                                                                                                Save
                                                                                            </button>
                                                                                        </div> -->
                                                </div>

                                            </div>
                                        </form>
                                    </div>
                                @endif
                            @endforeach

                        </div>
                    </div>



                    <!-- Footer -->
                    <div class="px-4 sm:px-6 py-3 border-t flex items-center justify-end gap-2">
                        <button class="px-4 py-2 text-sm rounded border border-gray-300 bg-white"
                            onclick="closeChecklistModal()">Cancel</button>
                        <button class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-teal-700">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Assign Modal -->
    <div id="equipmentAssignModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center px-4">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="relative px-6 pt-6 pb-4 border-b">
                <h2 class="text-xl font-semibold text-gray-900 text-center">Assign Equipment ID</h2>
                <button type="button"
                    class="close-equipment-assign-modal text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none absolute right-6 top-6">&times;</button>
            </div>

            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'equipmentAssignForm',
                ])->open() }}

            <div class="px-4 pt-3 space-y-2 overflow-y-auto">
                <!-- Order Information Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-2">
                    <div>
                        <span class="text-xs font-semibold text-blue-700">Order ID:</span>
                        <span id="assign-order-id" class="text-sm text-blue-900 font-semibold">-</span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-blue-700">Customer:</span>
                        <span id="assign-customer-name" class="text-sm text-blue-900 font-semibold">-</span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-blue-700">Product Ordered:</span>
                        <span id="assign-product-name" class="text-sm text-blue-900 font-semibold">-</span>
                    </div>
                </div>

                <!-- Use Currently Assigned Section -->
                <div id="useCurrentlyAssignedSection" class="hidden">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="assignment_type" id="useCurrentlyAssigned" value="current"
                                class="w-4 h-4">
                            <label for="useCurrentlyAssigned" class="text-sm font-semibold text-green-700">Use Currently
                                Assigned</label>
                        </div>
                        <div class="ml-6 space-y-1">
                            <div>
                                <span class="text-xs text-green-700">Equipment Name:</span>
                                <span id="soft-assigned-equipment" class="text-sm text-green-900 font-semibold">-</span>
                            </div>
                            <div>
                                <span class="text-xs text-green-700">Equipment ID:</span>
                                <span id="soft-assigned-id" class="text-sm text-green-900 font-semibold">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assign New Section -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <input type="radio" name="assignment_type" id="assignNew" value="new" class="w-4 h-4"
                            checked>
                        <label for="assignNew" class="text-sm font-semibold text-gray-700">Assign New Equipment ID</label>
                    </div>

                    <div id="newAssignmentFields" class="ml-6 space-y-2">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700 required">Category</label>
                            <select id="category_select"
                                class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm bg-white text-gray-700">
                                <option value="">Select Category</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700 required"
                                for="equipment_unique_id">Equipment</label>
                            <select name="equipment_unique_id" id="equipment_unique_id"
                                class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700">
                                <option value="" data-current-status="">Select Equipment</option>
                            </select>
                            <div class="flex items-center justify-between gap-3 mt-2">
                                <span id="equipment-status-display" class="text-sm font-semibold text-yellow-400"></span>
                                <a href="#"
                                    class="text-blue-600 hover:underline text-sm font-semibold"
                                    id="equipment-page-link"></a>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="order-product-unique-id" name="order_product_unique_id" value="">
                <input type="hidden" id="schedule-type" name="schedule_type" value="">
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button"
                    class="close-equipment-assign-modal px-6 py-3 rounded-lg font-medium text-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit" id="equipment-assign-submit"
                    class="px-6 py-3 rounded-lg font-medium text-md bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                    Save
                </button>
            </div>
            </form>
        </div>
    </div>

    <!-- Change Return Date Modal -->
    @php
        $hoursSettings = $allocatedHoursSettings ?? [];
        $dailyHours   = floatval($hoursSettings['daily_hours']   ?? 8);
        $weekendHours = round(floatval($hoursSettings['weekend_hours'] ?? 14) / 2.5, 1);
        $weeklyHours  = floatval($hoursSettings['weekly_hours']  ?? 40);
        $monthlyHours = floatval($hoursSettings['monthly_hours'] ?? 160);
    @endphp
    <div id="changeReturnDateModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center px-4">
        <div class="bg-white rounded-xl w-full max-w-md shadow-xl flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-4 border-b">
                <h2 class="text-base font-semibold text-gray-900">Change Return Date</h2>
                <button type="button" id="closeChangeReturnDateModal"
                    class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            <div class="px-6 py-4 space-y-3">

                <!-- ── Date summary bar ────────────────────────────────────── -->
                <div class="flex items-center justify-between rounded-lg bg-gray-50 border border-gray-200 px-4 py-2.5">
                    <div class="text-xs text-gray-500">
                        Current: <strong id="returnCurrentDateLabel" class="text-gray-700 font-semibold">—</strong>
                    </div>
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                        New Date:
                        <div class="relative">
                            <input type="text" id="returnManualDateInput" readonly
                                   class="border border-gray-300 rounded pl-2 pr-7 py-1 text-sm w-32 text-center cursor-pointer bg-white"
                                   placeholder="MM/DD/YYYY" autocomplete="off" />
                            <button type="button" id="returnManualDateIconBtn"
                                    class="absolute inset-y-0 right-1 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Option 1 -->
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="radio" name="returnDateOption" id="returnDateOnly" value="date_only"
                        class="w-4 h-4 accent-blue-600">
                    <span class="text-sm font-medium text-gray-800">Change Return Date only</span>
                </label>
                <!-- Option 2 -->
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="radio" name="returnDateOption" id="returnDateAndHours" value="date_and_hours"
                        class="w-4 h-4 accent-blue-600" checked>
                    <span class="text-sm font-medium text-gray-800">Change Return Date &amp; update Equipment hours</span>
                </label>

                <!-- Hours options -->
                <div id="returnHoursOptions" class="ml-7 space-y-2 pt-1">
                    @foreach ([
                        ['id' => 'hoursTypeDaily',   'value' => 'daily',   'label' => 'Daily',        'hours' => $dailyHours],
                        ['id' => 'hoursTypeWeekend', 'value' => 'weekend', 'label' => 'Weekend Spcl', 'hours' => $weekendHours],
                        ['id' => 'hoursTypeWeekly',  'value' => 'weekly',  'label' => 'Weekly',       'hours' => $weeklyHours],
                        ['id' => 'hoursTypeMonthly', 'value' => 'monthly', 'label' => 'Monthly',      'hours' => $monthlyHours],
                    ] as $hoursOption)
                    <div class="flex items-center justify-between gap-2">
                        <label class="flex items-center gap-2 cursor-pointer flex-1">
                            <input type="radio" name="hoursType" id="{{ $hoursOption['id'] }}"
                                value="{{ $hoursOption['value'] }}"
                                data-hours="{{ $hoursOption['hours'] }}"
                                class="hours-type-radio w-4 h-4 accent-blue-600"
                                @if($hoursOption['value'] === 'daily') checked @endif>
                            <span class="text-sm text-gray-700">{{ $hoursOption['label'] }} <span class="text-gray-500">{!! '{' . $hoursOption['hours'] . ' Hours}' !!}</span></span>
                        </label>
                        <div class="flex items-center gap-1 hours-qty-row" data-type="{{ $hoursOption['value'] }}">
                            <button type="button"
                                class="hours-qty-minus w-6 h-6 rounded-full bg-yellow-400 text-white font-bold text-sm flex items-center justify-center leading-none"
                                data-type="{{ $hoursOption['value'] }}">−</button>
                            <span class="hours-qty-display text-sm font-medium w-5 text-center"
                                data-type="{{ $hoursOption['value'] }}">1</span>
                            <button type="button"
                                class="hours-qty-plus w-6 h-6 rounded-full bg-yellow-400 text-white font-bold text-sm flex items-center justify-center leading-none"
                                data-type="{{ $hoursOption['value'] }}">+</button>
                            <span class="text-xs text-gray-400 ml-0.5">Qty</span>
                        </div>
                    </div>
                    @endforeach
                    <!-- Custom -->
                    <div class="flex items-center justify-between gap-2">
                        <label class="flex items-center gap-2 cursor-pointer flex-1">
                            <input type="radio" name="hoursType" id="hoursTypeCustom" value="custom"
                                data-hours="0"
                                class="hours-type-radio w-4 h-4 accent-blue-600">
                            <span class="text-sm text-gray-700">Custom</span>
                        </label>
                        <input type="number" id="customHoursInput" min="0" step="0.5"
                            class="border rounded px-2 py-1 text-sm w-20 text-right hidden"
                            placeholder="Hours">
                    </div>
                    <!-- Total: live equation — Allocated + Added = Total.
                         Values update live from recalcTotal(); Total Hours is
                         the finished sum, given slightly stronger emphasis. -->
                    <div class="pt-2 border-t">
                        <div class="flex flex-wrap items-center justify-end gap-x-1.5 gap-y-0.5 text-sm text-gray-700">
                            <span>Allocated Hours: <span id="returnCurrentAllocatedHours" class="font-semibold text-gray-900">0</span></span>
                            <span class="text-gray-400">+</span>
                            <span>Hours Added: <span id="returnTotalHoursAdded" class="font-semibold text-gray-900">0</span></span>
                            <span class="text-gray-400">=</span>
                            <span>Total Hours: <span id="returnGrandTotalHours" class="font-bold text-gray-900">0</span></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-xl">
                <button type="button" id="cancelChangeReturnDate"
                    class="px-5 py-2 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="button" id="saveChangeReturnDate"
                    class="px-5 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                    Save
                </button>
            </div>
        </div>
    </div>

{{-- View Damage Details modal (damage charges with a linked OrderProduct) --}}
<div id="beViewDamageModal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-xl border border-gray-200 overflow-hidden flex flex-col max-h-[80vh]">
        <div class="px-6 pt-4 border-b flex justify-between items-center pb-3">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Damage Details</h3>
                <p class="text-xs text-gray-500 mt-0.5">Checklist items and damage charges</p>
            </div>
            <button type="button" onclick="beCloseModal('beViewDamageModal')" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>
        <div class="px-6 py-4 overflow-auto">
            <div id="beViewDamageLoading" class="text-sm text-gray-500 py-4">Loading…</div>
            <div id="beViewDamageEmpty" class="hidden text-sm text-gray-500 text-center py-4">No damage detail records found.</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr class="text-left border-b">
                        <th class="py-2 px-2 text-xs">Checklist Item</th>
                        <th class="py-2 px-2 text-xs">Delivered</th>
                        <th class="py-2 px-2 text-xs">Returned</th>
                        <th class="py-2 px-2 text-xs text-right">Customer Owes</th>
                    </tr>
                </thead>
                <tbody id="beViewDamageTbody" class="text-gray-800"></tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t flex justify-end">
            <button type="button" onclick="beCloseModal('beViewDamageModal')"
                    class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition">
                Close
            </button>
        </div>
    </div>
</div>

{{-- Operations History modal (Enhancement 6/7). Placed at content root so no
     hidden ancestor can suppress it. Body is fetched on open into #opsHistoryBody. --}}
<div id="operationsHistoryModal"
    class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-start py-10">
    <div class="bg-white rounded-lg w-full max-w-3xl shadow-lg flex flex-col mx-4 max-h-[85vh]">
        <div class="flex justify-between items-center p-4 border-b">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Operations History</h2>
                <p class="text-xs text-gray-500">Order {{ $order->order_number }} — read-only operational audit trail</p>
            </div>
            <button type="button" data-ops-history-close
                class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none" aria-label="Close">&times;</button>
        </div>
        <div id="opsHistoryBody" class="flex-1 overflow-y-auto p-4">
            <div class="py-10 text-center text-sm text-gray-400">Loading…</div>
        </div>
    </div>
</div>

@endsection

@push('js')

    <script>
        // ── Header section reveals + PO popup ───────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {

            // Fast tooltips for the header action buttons. The native `title`
            // tooltip has a slow, browser-fixed delay, so we lift each title into
            // a custom tooltip that shows instantly on hover and remove the native
            // attribute to suppress the double/slow popup.
            (function () {
                const scope = document.getElementById('orderHeaderCard');
                if (!scope) return;
                let tip = document.getElementById('hdrTip');
                if (!tip) {
                    tip = document.createElement('div');
                    tip.id = 'hdrTip';
                    tip.className = 'pointer-events-none fixed z-[100000] hidden rounded-md bg-gray-900 px-2 py-1 text-xs font-medium text-white shadow-lg whitespace-nowrap';
                    document.body.appendChild(tip);
                }
                scope.querySelectorAll('[title]').forEach(function (el) {
                    const text = el.getAttribute('title');
                    if (!text) return;
                    el.setAttribute('data-tip', text);
                    el.removeAttribute('title');
                    el.addEventListener('mouseenter', function () {
                        tip.textContent = el.getAttribute('data-tip');
                        tip.classList.remove('hidden');
                        const r = el.getBoundingClientRect();
                        tip.style.left = (r.left + r.width / 2) + 'px';
                        tip.style.top = (r.bottom + 6) + 'px';
                        tip.style.transform = 'translateX(-50%)';
                    });
                    el.addEventListener('mouseleave', function () { tip.classList.add('hidden'); });
                });
            })();

            // Reveal a hidden section (scroll to it) and, when the trigger names an
            // in-section button (data-click), click it so the existing add-charge
            // modal logic is reused unchanged.
            document.querySelectorAll('[data-reveal]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const target = document.querySelector(btn.getAttribute('data-reveal'));
                    if (!target) return;
                    target.classList.remove('hidden');
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    const clickSel = btn.getAttribute('data-click');
                    if (clickSel) {
                        const el = document.querySelector(clickSel);
                        if (el) setTimeout(function () { el.click(); }, 250);
                    }
                });
            });

            // PO add/edit popup open/close (the Save button uses the existing
            // .edit-po-btn handler, so saving behavior is unchanged).
            const poModal = document.getElementById('poModal');
            if (poModal) {
                document.querySelectorAll('[data-po-open]').forEach(function (b) {
                    b.addEventListener('click', function () {
                        poModal.style.display = 'flex';
                        poModal.querySelector('#po_id')?.focus();
                    });
                });
                poModal.querySelectorAll('[data-po-close]').forEach(function (b) {
                    b.addEventListener('click', function () { poModal.style.display = 'none'; });
                });
                poModal.addEventListener('click', function (e) {
                    if (e.target === poModal) poModal.style.display = 'none';
                });
            }
        });
    </script>

    <script>
        // ── Delete Order ──────────────────────────────────────────────────────────
        document.getElementById('delete-order-btn')?.addEventListener('click', function () {
            const uniqueId = this.dataset.uniqueId;

            // Extension child: one transaction with its Rental Extension
            // charge — use the coordinated confirmation (disposition-aware)
            if (this.dataset.extChild) {
                window.extDeleteFlow.open({
                    contextHtml: 'Deleting child order <span class="font-semibold">#' + this.dataset.extNumber + '</span> '
                        + 'will also remove its linked Order Enhancement charge from parent order '
                        + '<span class="font-semibold">#' + this.dataset.extParent + '</span>.'
                        + '<br><span class="font-semibold text-red-700">Both records will be affected.</span>',
                    paystate: this.dataset.extPaystate,
                    url: '{{ route('admin.order-management.orders.bulk-delete') }}',
                    payload: { unique_ids: [uniqueId] },
                    onSuccess: function (data) {
                        if (window.notyf) notyf.success(data.message || 'Extension transaction deleted.');
                        setTimeout(function () {
                            window.location.href = '{{ route('admin.order-management.orders.index') }}';
                        }, 800);
                    },
                });
                return;
            }

            window.showConfirm(
                'Are you sure you want to delete this order? Deleted orders cannot be recovered.',
                'Delete Order'
            ).then(function (result) {
                if (!result.isConfirmed) return;
                apiFetch('{{ route('admin.order-management.orders.bulk-delete') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ unique_ids: [uniqueId] }),
                }).then(function (data) {
                    if (data && (data.success || (data.message && data.message.toLowerCase().includes('deleted')))) {
                        if (window.notyf) notyf.success(data.message || 'Order deleted.');
                        setTimeout(function () {
                            window.location.href = '{{ route('admin.order-management.orders.index') }}';
                        }, 800);
                    } else {
                        if (window.notyf) notyf.error(data?.message || 'Failed to delete order.');
                    }
                }).catch(function (err) {
                    console.error('Delete order error:', err);
                    if (window.notyf) notyf.error('Request failed. Please try again.');
                });
            });
        });
    </script>

    <script>
        const modalEl = document.getElementById('checklistModal');
        const checklistProductNames = @json($order->products->mapWithKeys(fn($p) => [$p->id => $p->product_name]));

        function openChecklistModal(orderProductId) {
            modalEl.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // scroll-lock

            const productId = String(orderProductId);
            const titleEl = document.getElementById('checklistProductTitle');
            if (titleEl) titleEl.textContent = checklistProductNames[productId] || checklistProductNames[orderProductId] || '';
            const sum = (selector) => Array.from(modalEl.querySelectorAll(selector))
                .filter(el => el.dataset.productId === productId)
                .reduce((total, el) => total + (parseFloat(el.dataset.amount) || 0), 0);

            // Show only the rows/panels belonging to the clicked product
            modalEl.querySelectorAll('.checklist-row, .hour-row, .damage-row, .fuel-row').forEach(row => {
                row.classList.toggle('hidden', row.dataset.productId !== productId);
            });
            modalEl.querySelectorAll('.checklist-notes-panel').forEach(panel => {
                panel.classList.toggle('hidden', panel.dataset.productId !== productId);
            });

            // Recompute totals for just this product
            const checklistTotal = sum('.checklist-row');
            const hourTrackingTotal = sum('.hour-row');
            const damageTotal = sum('.damage-row');
            const fuelTotal = sum('.fuel-row');
            const grandTotal = checklistTotal + hourTrackingTotal + damageTotal + fuelTotal;

            const fmt = (n) => '$' + n.toFixed(2);
            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value;
            };
            setText('checklistTotalDisplay', fmt(checklistTotal));
            setText('hourTrackingTotalDisplay', fmt(hourTrackingTotal));
            setText('damageTotalDisplay', fmt(damageTotal));
            setText('fuelTotalDisplay', fmt(fuelTotal));
            setText('grandTotalDisplay', grandTotal.toFixed(2));

            const damageRowHasData = modalEl.querySelectorAll(`.damage-row[data-product-id="${productId}"]`).length > 0;
            const fuelRowHasData = modalEl.querySelectorAll(`.fuel-row[data-product-id="${productId}"]`).length > 0;
            document.getElementById('damageTotalRow')?.classList.toggle('hidden', !damageRowHasData);
            document.getElementById('fuelTotalRow')?.classList.toggle('hidden', !fuelRowHasData);

            // focus first control for a11y
            setTimeout(() => {
                const first = modalEl.querySelector('select, input, button');
                first && first.focus();
            }, 0);
        }

        function closeChecklistModal() {
            modalEl.classList.add('hidden');
            document.body.style.overflow = ''; // restore scroll
        }
        // ESC to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modalEl.classList.contains('hidden')) {
                closeChecklistModal();
            }
        });
    </script>


    @if ($paymentSetting['payment_test_mode'] ?? false)
        <script src="https://jstest.authorize.net/v1/Accept.js"></script>
    @else
        <script src="https://js.authorize.net/v1/Accept.js"></script>
    @endif
    <script>
        // Get the order id (replace with actual variable)
        const orderUniqueId = '{{ $order->unique_id }}';
        // Note Modal logic
        const noteModal = document.getElementById('noteModal');
        const noteForm = document.getElementById('noteForm');
        const noteText = document.getElementById('note_text');
        const userIdInput = document.getElementById('user_id');
        const addNoteBtn = document.getElementById('addNoteBtn');
        const noteModalTitle = document.getElementById('noteModalTitle');
        const noteDiv = document.getElementById('noteDiv');

        // Fetch and refresh notes list
        function fetchNotes() {
            if (!noteDiv) return;
            const fetchUrl = '{{ route('admin.order-management.orders.notes.index', [':unique_id']) }}'.replace(
                ':unique_id', orderUniqueId);
            apiFetch(fetchUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                }, {
                    loaderSelector: '#note-loading',
                    containerSelector: '#noteDiv'
                })
                .then(res => {
                    if (res && res.success) {
                        let html = '';
                        noteDiv.innerHTML = res.html;
                        //notyf.success(res.message);
                    } else {
                        notyf.error(res.message);
                    }
                })
        }

        // Delete Note
        document.addEventListener('click', function(e) {
            if (e.target.closest('button[title="Delete Note"]')) {
                const btn = e.target.closest('button');
                const noteId = btn.getAttribute('data-note-id');

                showConfirm('Do you want to delete this note?', 'Are you sure?').then((result) => {
                    if (result.isConfirmed) {
                        const url =
                            '{{ route('admin.order-management.orders.notes.delete', [':unique_id', ':noteId']) }}'
                            .replace(':unique_id', orderUniqueId)
                            .replace(':noteId', noteId);

                        apiFetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        }).then(res => {
                            if (res && res.success) {
                                notyf.success(res.message);
                                fetchNotes(); // reload the notes list
                            } else {
                                notyf.error(res.message);
                            }
                        })
                    }
                });
            }
        });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-equipment-btn');
            if (!btn) return;

            const orderProductUniqueId = btn.dataset.orderProductUniqueId;
            if (!orderProductUniqueId) return;

            showConfirm('Remove this equipment?', 'This will clear the equipment assignment.').then((result) => {
                if (!result.isConfirmed) return;

                apiFetch('{{ route('admin.order-management.orders.remove-equipment') }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            order_product_unique_id: orderProductUniqueId
                        })
                    })
                    .then(res => {
                        if (res && res.success) {
                            notyf.success(res.message || 'Equipment removed successfully.');
                            window.location.reload();
                        } else {
                            notyf.error(res && res.message ? res.message : 'Failed to remove equipment.');
                        }
                    })
                    .catch(() => {
                        notyf.error('Failed to remove equipment.');
                    });
            });
        });

        // Delegated event: Edit Note
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('button[title="Edit Note"]');
            if (!btn) return;

            // Get data from attributes
            const noteId = btn.dataset.noteId;
            const noteText = btn.dataset.noteText;
            const userId = btn.dataset.userId;

            // Populate modal fields
            document.getElementById('note_text').value = noteText;
            document.getElementById('user_id').value = userId;
            document.getElementById('noteModalTitle').textContent = 'Edit Note';

            // Add hidden input to track edit mode
            let hidden = document.getElementById('editNoteId');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'note_id';
                hidden.id = 'editNoteId';
                document.getElementById('noteForm').appendChild(hidden);
            }
            hidden.value = noteId;

            // Show modal
            document.getElementById('noteModal').classList.remove('hidden');
        });

        document.addEventListener('DOMContentLoaded', function() {
            window.closeNoteModal = function() {
                noteModal.classList.add('hidden');
                noteForm.reset();

                const editField = document.getElementById('editNoteId');
                if (editField) {
                    editField.remove();
                }
            }

            if (addNoteBtn) {
                addNoteBtn.addEventListener('click', function() {
                    noteModalTitle.textContent = 'Add Note';
                    noteModal.classList.remove('hidden');
                    noteText.value = '';
                    noteText.focus();
                });
            }

            // Optionally: Allow closing note modal with Esc key
            document.addEventListener('keydown', function(event) {
                if (!noteModal.classList.contains('hidden') && event.key === "Escape") {
                    closeNoteModal();
                }
            });

            // Handle note form submit (AJAX logic to be added as needed)
            noteForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!$(noteForm).parsley().isValid()) {
                    $(noteForm).parsley().validate();
                    return;
                }

                let url;
                let method = 'POST';
                // Save note via AJAX using apiFetch
                const note = noteText.value.trim();
                const userId = userIdInput.value;
                const editField = document.getElementById('editNoteId');

                if (editField && editField.value) {
                    // Editing existing note
                    url =
                        '{{ route('admin.order-management.orders.notes.update', [':unique_id', ':note_id']) }}'
                        .replace(':unique_id', orderUniqueId)
                        .replace(':note_id', editField.value);
                    method = 'PUT';
                } else {
                    // Adding new note
                    url = '{{ route('admin.order-management.orders.notes.store', [':unique_id']) }}'
                        .replace(':unique_id', orderUniqueId);

                }

                const submitBtn = noteForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';

                apiFetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            note: note,
                            user_id: userId
                        })
                    })
                    .then(res => {
                        if (res && res.success) {
                            notyf.success(res.message);
                            closeNoteModal(); // Close modal after saving
                            fetchNotes();
                        } else {
                            notyf.error(res && res.message ? res.message : '');
                        }
                    })
                    .finally(() => {
                        // After a successful update
                        if (editField) {
                            editField.remove(); // Remove the hidden input
                        }
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
            });


            // Confirm payment button
            // const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
            // if (confirmPaymentBtn) {
            //     confirmPaymentBtn.addEventListener('click', function() {
            //         showConfirm('Do you want to confirm this payment?', 'Are you sure?').then((result) => {
            //             if (result.isConfirmed) {
            //                 confirmPaymentBtn.disabled = true;
            //                 confirmPaymentBtn.textContent = 'Processing...';
            //                 let url =
            //                     '{{ route('admin.order-management.orders.confirm-payment', ':unique_id') }}';
            //                 url = url.replace(':unique_id', orderUniqueId);
            //                 apiFetch(url, {
            //                         method: 'POST',
            //                         headers: {
            //                             'Content-Type': 'application/json',
            //                             'X-CSRF-TOKEN': document.querySelector(
            //                                 'meta[name="csrf-token"]').getAttribute(
            //                                 'content')
            //                         },
            //                         body: JSON.stringify({
            //                             _method: 'PUT'
            //                         })
            //                     })
            //                     .then(res => {
            //                         if (res && res.success) {
            //                             notyf.success('Payment confirmed!');
            //                             setTimeout(() => window.location.reload(), 800);
            //                         } else {
            //                             notyf.error(res && res.message ? res.message :
            //                                 'Failed to confirm payment.');
            //                         }
            //                     })
            //                     .catch(() => {
            //                         notyf.error('Failed to confirm payment.');
            //                     })
            //                     .finally(() => {
            //                         confirmPaymentBtn.disabled = false;
            //                         confirmPaymentBtn.textContent = 'Confirm payment';
            //                     });
            //             }
            //         });
            //     });
            // }


            // Add To Account button
            const addToAccountBtn = document.getElementById('addToAccountBtn');
            if (addToAccountBtn) {
                addToAccountBtn.addEventListener('click', function() {
                    showConfirm('Do you want to add this to the account?', 'Are you sure?').then((
                        result) => {
                        if (result.isConfirmed) {
                            addToAccountBtn.disabled = true;
                            addToAccountBtn.textContent = 'Processing...';
                            let url =
                                '{{ route('admin.order-management.orders.add-to-account', ':unique_id') }}';
                            url = url.replace(':unique_id', orderUniqueId);

                            apiFetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content')
                                    },
                                    body: JSON.stringify({
                                        _method: 'PUT'
                                    })
                                })
                                .then(res => {
                                    if (res && res.success) {
                                        notyf.success('Added to account successfully!');
                                        setTimeout(() => window.location.reload(), 800);
                                    } else {
                                        notyf.error(res && res.message ? res.message :
                                            'Failed to add to account.');
                                    }
                                })
                                .catch(() => {
                                    notyf.error('Failed to add to account.');
                                })
                                .finally(() => {
                                    addToAccountBtn.disabled = false;
                                    addToAccountBtn.textContent = 'Add to Account';
                                });
                        }
                    });
                });
            }


            // const saveBtn = document.getElementById('saveNoteBtn');
            // const noteInput = document.getElementById('order_note');

            // saveBtn.addEventListener('click', function() {
            //     const note = noteInput.value;

            //     // UI: Disable and show saving...
            //     saveBtn.disabled = true;
            //     const originalText = saveBtn.textContent;
            //     saveBtn.textContent = 'Saving...';

            //     url = '{{ route('admin.order-management.orders.update-note', ':unique_id') }}';
            //     url = url.replace(':unique_id', orderUniqueId);

            //     let data = {
            //         order_note: note,
            //         _method: 'PUT'
            //     };

            //     apiFetch(url, {
            //             method: 'POST',
            //             headers: {
            //                 'Content-Type': 'application/json',
            //                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
            //                     .getAttribute('content')
            //             },
            //             body: JSON.stringify(data)
            //         })
            //         .then(resData => {
            //             notyf.success('Order note updated successfully!');
            //         })
            //         .finally(() => {
            //             saveBtn.disabled = false;
            //             saveBtn.textContent = originalText;
            //         });
            // });


            // ── Schedule date utilities (MM/dd/yyyy ↔ Date) ──────────────────────
            const parseScheduleDate = window.parseScheduleDate || function(str) {
                if (!str) return null;
                const parts = str.split('/');
                if (parts.length !== 3) return null;
                const [m, d, y] = parts.map(Number);
                if (!m || !d || !y) return null;
                return new Date(y, m - 1, d);
            };
            const formatScheduleDate = window.formatScheduleDate || function(date) {
                if (!(date instanceof Date) || isNaN(date)) return '';
                return String(date.getMonth() + 1).padStart(2, '0') + '/' +
                       String(date.getDate()).padStart(2, '0') + '/' +
                       date.getFullYear();
            };

            window.parseScheduleDate = parseScheduleDate;
            window.formatScheduleDate = formatScheduleDate;

            document.querySelectorAll('.order-product-unique-id').forEach(function(hiddenInput, idx) {
                const container = hiddenInput.closest('.bg-white.rounded-xl.border');
                if (!container) return;

                const orderProductId = hiddenInput.value;

                // Delivery fields
                const deliveryDate = container.querySelector('.delivery_date');
                const deliveryTime = container.querySelector('.delivery_time');
                const deliveryType = container.querySelector('input[type=hidden].delivery_transport_mode');
                const deliveryStatus = container.querySelector('.delivery_status');
                const deliveryLocation = container.querySelector('.delivery_store_id');
                const deliveryTechnician = container.querySelector('.delivery_by');

                // Return fields
                const returnDate = container.querySelector('.pickup_date');
                const returnTime = container.querySelector('.pickup_time');
                const returnType = container.querySelector('input[type=hidden].pickup_transport_mode');
                const returnStatus = container.querySelector('.pickup_status');
                const returnLocation = container.querySelector('.pickup_store_id');
                const returnTechnician = container.querySelector('.pickup_by');

                // Helper to update only the changed field for delivery/return
                function updateScheduleField(type, field, value) {
                    let url =
                        '{{ route('admin.order-management.orders.update-product-schedule', [':order_unique_id', ':product_unique_id']) }}';
                    url = url.replace(':order_unique_id', orderUniqueId).replace(':product_unique_id',
                        orderProductId);

                    // Map field to DB column
                    let dbField = '';
                    if (type === 'delivery') {
                        dbField = field;
                    } else if (type === 'return') {
                        dbField = field;
                    }

                    let data = {
                        _method: 'PUT',
                        type: type
                    };
                    data[dbField] = value;

                    return apiFetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify(data)
                        })
                        .then(res => {
                            notyf.success(res && res.message ? res.message : 'Schedule updated!');
                            return res;
                        })
                        .catch(() => {
                            notyf.error('Failed to update schedule.');
                        });
                }

                // Delivery listeners
                if (deliveryDate) {
                    deliveryDate.setAttribute('data-previous-value', deliveryDate.value || '');

                    deliveryDate.addEventListener('focus', function() {
                        this.setAttribute('data-previous-value', this.value || '');
                    });

                    deliveryDate.addEventListener('change', function() {
                        const prevDelivery = this.getAttribute('data-previous-value') || '';
                        const newDelivery  = this.value;
                        this.setAttribute('data-previous-value', newDelivery);

                        // Auto-shift return date to maintain the same calendar duration.
                        // No error — return date moves with the delivery date.
                        if (returnDate && returnDate.value && prevDelivery && newDelivery) {
                            const prevDel = parseScheduleDate(prevDelivery);
                            const curRet  = parseScheduleDate(returnDate.value);
                            const newDel  = parseScheduleDate(newDelivery);
                            if (prevDel && curRet && newDel) {
                                const durationMs = curRet.getTime() - prevDel.getTime();
                                const newRet     = new Date(newDel.getTime() + durationMs);
                                const newRetStr  = formatScheduleDate(newRet);
                                // Update the return date field on screen
                                returnDate.value = newRetStr;
                                returnDate.setAttribute('data-previous-value', newRetStr);
                                if (returnDate._airDatepicker) {
                                    returnDate._airDatepicker.selectDate(newRet, { silent: true });
                                }
                                // Save delivery date then the auto-shifted return date
                                // (allocated hours are intentionally NOT touched)
                                updateScheduleField('delivery', 'delivery_date', newDelivery);
                                updateScheduleField('return', 'pickup_date', newRetStr);
                                return;
                            }
                        }

                        updateScheduleField('delivery', 'delivery_date', newDelivery);
                    });
                }
                if (deliveryTime) {
                    // Try to parse the initial value as a Date (if it exists)
                    let lastValue = deliveryTime.value ?
                        flatpickr.parseDate(deliveryTime.value, "h:i K") :
                        null;

                    flatpickr(deliveryTime, {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "h:i K",
                        time_24hr: false,
                        onClose: function(selectedDates, dateStr) {
                            // Parse dateStr to a Date object (or null)
                            const newValue = dateStr ?
                                flatpickr.parseDate(dateStr, "h:i K") :
                                null;

                            // Only call if both are valid dates and times are different
                            // Or if lastValue was null but now we have a value
                            const changed = (
                                (lastValue && newValue && newValue.getTime() !== lastValue
                                    .getTime()) ||
                                (!lastValue && newValue)
                            );

                            if (changed) {
                                updateScheduleField('delivery', 'delivery_time', dateStr);
                                lastValue = newValue;
                            }
                        }
                    });
                }
                if (deliveryType) {
                    deliveryType.addEventListener('change', function() {
                        console.log('Delivery type changed to', deliveryType.value);
                        updateScheduleField('delivery', 'delivery_transport_mode', deliveryType
                            .value);
                    });
                }
                if (deliveryStatus) {
                    deliveryStatus.addEventListener('change', function() {
                        if (this.dataset.reverting === '1') {
                            this.dataset.reverting = '';
                            return;
                        }

                        const previousValue = this.getAttribute('data-previous-value') || '';
                        const newValue = this.value;

                        // Check if status changed to Completed
                        if (newValue === 'Completed' && previousValue !== 'Completed') {
                            // Get order product data
                            const orderProducts = @json($order->products);
                            const orderProduct = orderProducts.find(op => op.unique_id ===
                                orderProductId);

                            // Check if equipment is already assigned
                            if (!orderProduct?.equipment_id) {
                                // Open equipment assignment modal
                                const softAssignment = orderProduct?.soft_assignment || null;
                                window.openEquipmentAssignModal(orderProductId, 'Delivery',
                                    softAssignment);

                                this.dataset.reverting = '1';
                                this.value = previousValue || 'Pending';
                                this.setAttribute('data-previous-value', this.value);
                                this.dispatchEvent(new Event('change'));

                                // Don't update status yet - will be done after equipment assignment
                                return;
                            }
                        }

                        // Store current value as previous for next change
                        this.setAttribute('data-previous-value', newValue);
                        updateScheduleField('delivery', 'delivery_status', deliveryStatus.value)
                            .then(() => {
                                window.location.reload();
                            });
                    });

                    // Initialize with current value
                    if (deliveryStatus.value) {
                        deliveryStatus.setAttribute('data-previous-value', deliveryStatus.value);
                    }
                }
                if (deliveryLocation) {
                    deliveryLocation.addEventListener('change', function() {
                        updateScheduleField('delivery', 'delivery_store_id', deliveryLocation
                            .value);
                    });
                }
                if (deliveryTechnician) {
                    deliveryTechnician.addEventListener('change', function() {
                        updateScheduleField('delivery', 'delivery_by', deliveryTechnician.value);
                        if (deliveryTechnician.value) {
                            deliveryTechnician.disabled = true;
                        }
                    });
                }

                // Return date — clicking opens the modal directly (bypasses AirDatepicker)
                if (returnDate) {
                    returnDate.setAttribute('data-previous-value', returnDate.value || '');

                    returnDate.addEventListener('mousedown', function(e) {
                        if (e.button !== 0) return; // only left-click
                        e.preventDefault(); // stop focus → stop AirDatepicker from opening

                        const orderProducts         = @json($order->products);
                        const orderProduct          = orderProducts.find(op => op.unique_id === orderProductId);
                        const currentAllocatedHours = parseFloat(orderProduct?.allocated_hours) || 0;

                        window.openChangeReturnDateModal(
                            orderProductId,
                            returnDate.value,                           // current return date
                            deliveryDate ? deliveryDate.value : '',     // delivery date (for calc)
                            updateScheduleField,
                            currentAllocatedHours,
                            returnDate                                  // DOM element to update on save
                        );
                    });
                }
                if (returnTime) {
                    // Store the initial value as a Date object (if possible)
                    let lastValue = returnTime.value ?
                        flatpickr.parseDate(returnTime.value, "h:i K") :
                        null;

                    flatpickr(returnTime, {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "h:i K", // 12-hour format
                        time_24hr: false,
                        onClose: function(selectedDates, dateStr) {
                            // Parse the new value as a Date object
                            const newValue = dateStr ?
                                flatpickr.parseDate(dateStr, "h:i K") :
                                null;

                            // Only trigger if the date/time actually changed
                            const changed = (
                                (lastValue && newValue && newValue.getTime() !== lastValue
                                    .getTime()) ||
                                (!lastValue && newValue)
                            );

                            if (changed) {
                                updateScheduleField('return', 'pickup_time', dateStr);
                                lastValue = newValue; // Save new value for next comparison
                            }
                        }
                    });
                }
                if (returnType) {
                    returnType.addEventListener('change', function() {
                        updateScheduleField('return', 'pickup_transport_mode', returnType.value);
                    });
                }
                if (returnStatus) {
                    returnStatus.addEventListener('change', function() {
                        // const previousValue = this.getAttribute('data-previous-value') || '';
                        // const newValue = this.value;

                        // // Check if status changed to Completed
                        // if (newValue === 'Completed' && previousValue !== 'Completed') {
                        //     // Get order product data
                        //     const orderProducts = @json($order->products);
                        //     const orderProduct = orderProducts.find(op => op.unique_id === orderProductId);

                        //     // Check if equipment is already assigned
                        //     if (!orderProduct?.equipment_id) {
                        //         // Open equipment assignment modal
                        //         const softAssignment = orderProduct?.soft_assignment || null;
                        //         window.openEquipmentAssignModal(orderProductId, 'Return', softAssignment);

                        //         // Don't update status yet - will be done after equipment assignment
                        //         return;
                        //     }
                        // }

                        // // Store current value as previous for next change
                        // this.setAttribute('data-previous-value', newValue);
                        updateScheduleField('return', 'pickup_status', returnStatus.value)
                            .then(() => {
                                window.location.reload();
                            });
                    });

                    // Initialize with current value
                    // if (returnStatus.value) {
                    //     returnStatus.setAttribute('data-previous-value', returnStatus.value);
                    // }
                }
                if (returnLocation) {
                    returnLocation.addEventListener('change', function() {
                        updateScheduleField('return', 'pickup_store_id', returnLocation.value);
                    });
                }
                if (returnTechnician) {
                    returnTechnician.addEventListener('change', function() {
                        updateScheduleField('return', 'pickup_by', returnTechnician.value);
                        if (returnTechnician.value) {
                            returnTechnician.disabled = true;
                        }
                    });
                }
            });

            // Keep track of what type of address we're editing
            let currentEditType = null; // 'Billing' or 'Shipping'
            let currentAddressData = {}; // will hold current address info

            // Open modal and fill data
            function openAddressModal(type, addressData = {}) {
                currentEditType = type; // "Billing" or "Shipping"
                currentAddressData = addressData || {};

                // Set the modal title
                document.getElementById('addressModalTitle').textContent =
                    `Edit ${(type == 'Billing' ? 'Billing' : 'Delivery')} Address`;

                // Set the type select field
                document.getElementById('type').value = type;

                // Fill the fields
                document.getElementById('first_name').value = addressData.first_name || '';
                document.getElementById('last_name').value = addressData.last_name || '';
                document.getElementById('email').value = addressData.email || '';
                document.getElementById('phone').value = addressData.phone || '';
                document.getElementById('address').value = addressData.address || '';
                document.getElementById('state').value = addressData.state || '';
                document.getElementById('city').value = addressData.city || '';
                document.getElementById('zip_code').value = addressData.zip_code || '';

                // Show modal
                document.getElementById('addressModal').classList.remove('hidden');
            }

            window.closeModal = function() {
                document.getElementById('addressModal').classList.add('hidden');
            }

            // Hook up the edit buttons (set IDs on your edit icons!)
            document.getElementById('editBillingBtn').addEventListener('click', function() {
                // Get current values from the billing info section
                const billingData = {
                    first_name: document.getElementById('billing_first_name_input')?.dataset
                        .firstName || '',
                    last_name: document.getElementById('billing_last_name_input')?.dataset.lastName ||
                        '',
                    email: document.getElementById('billing_email_input')?.dataset.email || '',
                    phone: document.getElementById('billing_phone_input')?.dataset.phone || '',
                    address: document.getElementById('billing_address_input')?.dataset.address || '',
                    state: document.getElementById('billing_state_input')?.dataset.state || '',
                    city: document.getElementById('billing_city_input')?.dataset.city || '',
                    zip_code: document.getElementById('billing_zip_code_input')?.dataset.zipCode || ''
                };
                openAddressModal('Billing', billingData);
            });
            document.getElementById('editShippingBtn').addEventListener('click', function() {
                // Get current values from the shipping info section
                const shippingData = {
                    first_name: document.getElementById('shipping_first_name_input')?.dataset
                        .firstName || '',
                    last_name: document.getElementById('shipping_last_name_input')?.dataset.lastName ||
                        '',
                    email: document.getElementById('shipping_email_input')?.dataset.email || '',
                    phone: document.getElementById('shipping_phone_input')?.dataset.phone || '',
                    address: document.getElementById('shipping_address_input')?.dataset.address || '',
                    state: document.getElementById('shipping_state_input')?.dataset.state || '',
                    city: document.getElementById('shipping_city_input')?.dataset.city || '',
                    zip_code: document.getElementById('shipping_zip_code_input')?.dataset.zipCode || ''
                };
                openAddressModal('Shipping', shippingData);
            });

            // Form submit

            document.getElementById('addressForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = e.target;

                if (!$(form).parsley().isValid()) {
                    $(form).parsley().validate();
                    return;
                }
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';

                // Ensure type field matches currentEditType
                formData.set('type', currentEditType);

                // Find selected state name and add to formData
                const stateSelect = form.querySelector('#state');
                if (stateSelect) {
                    const selectedStateName = stateSelect.options[stateSelect.selectedIndex].text;
                    formData.set('state', selectedStateName);
                }

                // Unified endpoint
                const endpoint =
                    '{{ route('admin.order-management.orders.update-address', ['unique_id' => $order->unique_id]) }}';

                apiFetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: formData
                    })
                    .then(res => {
                        if (res && res.success) {
                            notyf.success(res.message);
                            closeModal();
                            const addressData = res.data?.address || {};

                            if (res.data?.same_as_billing) {
                                document.querySelectorAll('.shipping-address-section').forEach(el => el
                                    .classList.add('hidden'));
                                document.getElementById('shipping_same_as_billing').classList.remove(
                                    'hidden');
                            } else {
                                document.querySelectorAll('.shipping-address-section').forEach(el => el
                                    .classList.remove('hidden'));
                                document.getElementById('shipping_same_as_billing').classList.add(
                                    'hidden');
                            }
                            // Update the address section in the UI without reload
                            if (currentEditType === 'Billing') {

                                document.getElementById('billing_first_name_input').dataset.firstName =
                                    addressData.first_name || '';
                                document.getElementById('billing_last_name_input').dataset.lastName =
                                    addressData.last_name || '';
                                document.getElementById('billing_email_input').dataset.email =
                                    addressData.email || '';
                                document.getElementById('billing_phone_input').dataset.phone =
                                    addressData.phone || '';
                                document.getElementById('billing_address_input').dataset.address =
                                    addressData.address || '';
                                document.getElementById('billing_state_input').dataset.state =
                                    addressData.state_id || '';
                                document.getElementById('billing_city_input').dataset.city = addressData
                                    .city || '';
                                document.getElementById('billing_zip_code_input').dataset.zipCode =
                                    addressData.zip_code || '';

                                document.getElementById('billing_name').textContent = addressData
                                    .full_name || '';
                                document.getElementById('billing_email').innerHTML = addressData.email ?
                                    `<a href="mailto:${addressData.email}" class="text-blue-600 hover:underline">${addressData.email}</a>` :
                                    '<span class="text-gray-500">N/A</span>';
                                document.getElementById('billing_phone').textContent = addressData
                                    .phone || '';
                                document.getElementById('billing_address').textContent = addressData
                                    .full_address || '';
                                const mapLink = document.getElementById('billing_map_link')
                                    ?.querySelector('a');
                                if (mapLink && addressData.full_address) {
                                    mapLink.href =
                                        `https://maps.google.com/?q=${encodeURIComponent(addressData.full_address)}`;
                                    mapLink.classList.remove('hidden');
                                } else if (mapLink) {
                                    mapLink.href = '#';
                                    mapLink.classList.add('hidden');
                                }
                            } else if (currentEditType === 'Shipping') {
                                document.getElementById('shipping_name').textContent = addressData
                                    .full_name || '';
                                document.getElementById('shipping_email').innerHTML = addressData
                                    .email ?
                                    `<a href="mailto:${addressData.email}" class="text-blue-600 hover:underline">${addressData.email}</a>` :
                                    '<span class="text-gray-500">N/A</span>';
                                document.getElementById('shipping_phone').textContent = addressData
                                    .phone || '';
                                document.getElementById('shipping_address').textContent = addressData
                                    .full_address || '';
                                const mapLink = document.getElementById('shipping_map_link')
                                    ?.querySelector('a');
                                if (mapLink && addressData.full_address) {
                                    mapLink.href =
                                        `https://maps.google.com/?q=${encodeURIComponent(addressData.full_address)}`;
                                    mapLink.classList.remove('hidden');
                                } else if (mapLink) {
                                    mapLink.href = '#';
                                    mapLink.classList.add('hidden');
                                }

                                document.getElementById('shipping_first_name_input').dataset.firstName =
                                    addressData.first_name || '';
                                document.getElementById('shipping_last_name_input').dataset.lastName =
                                    addressData.last_name || '';
                                document.getElementById('shipping_email_input').dataset.email =
                                    addressData.email || '';
                                document.getElementById('shipping_phone_input').dataset.phone =
                                    addressData.phone || '';
                                document.getElementById('shipping_address_input').dataset.address =
                                    addressData.address || '';
                                document.getElementById('shipping_state_input').dataset.state =
                                    addressData.state_id || '';
                                document.getElementById('shipping_city_input').dataset.city =
                                    addressData.city || '';
                                document.getElementById('shipping_zip_code_input').dataset.zipCode =
                                    addressData.zip_code || '';
                            }

                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            notyf.error(res && res.message);
                        }
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
            });

            // Optionally: Allow closing modal with Esc key
            document.addEventListener('keydown', function(event) {
                if (event.key === "Escape") {
                    closeModal();
                }
            });

            // Modal open/close logic
            const reorderBtn = document.getElementById('reorderBtn');
            const reorderModal = document.getElementById('reorderModal');
            const orderTypeSelect = document.getElementById('orderTypeSelect');
            const existingOrderSection = document.getElementById('existingOrderSection');

            function openReorderModal() {
                orderTypeSelect.value = 'new';
                orderTypeSelect.dispatchEvent(new Event('change'));
                document.querySelectorAll('.reorder-datepicker').forEach(el => {
                    el.value = '';
                    if (el._airDatepicker) {
                        el._airDatepicker.clear();
                    }
                });
                reorderModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeReorderModal() {
                reorderModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
            if (reorderBtn) {
                reorderBtn.addEventListener('click', openReorderModal);
            }

            // ✅ attach to BOTH close buttons
            document.querySelectorAll('.close-reorder-modal-btn').forEach(btn => btn.addEventListener('click',
                closeReorderModal));

            if (orderTypeSelect) {
                orderTypeSelect.addEventListener('change', function() {
                    if (this.value === 'existing_order') {
                        existingOrderSection.classList.remove('hidden');
                    } else {
                        existingOrderSection.classList.add('hidden');
                    }
                });
                // On load, ensure correct section is shown
                if (orderTypeSelect.value === 'existing_order') {
                    existingOrderSection.classList.remove('hidden');
                }
            }

            document.querySelectorAll('.reorder-datepicker').forEach(el => {
                // Only initialize if the input is visible (not .hidden)
                if (!el.classList.contains('hidden')) {
                    el._airDatepicker = new AirDatepicker(el, {
                        locale: window.airDatepickerLocaleEn,
                        timepicker: false,
                        dateFormat: el.dataset.format || window.APP_DATE_FORMAT ||
                            'yyyy-MM-dd HH:mm',
                        minDate: el.dataset.minDate ? new Date(el.dataset.minDate) : false,
                        autoClose: true,
                        keyboardNav: true,
                        // 🔹 Put the calendar in <body> so it’s not clipped or stuck
                        container: "#reorderModal",
                        // 🔹 Give it a stacking level above your modal overlay
                        zIndex: 99999
                    });
                }
            });


            document.getElementById('reorderForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
                // Unified endpoint
                const endpoint =
                    '{{ route('admin.order-management.orders.reorder', ['unique_id' => $order->unique_id]) }}';

                const response = apiFetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: formData
                    })
                    .then(res => {
                        if (res && res.success) {
                            setTimeout(() => {
                                notyf.success(res.message);
                            }, 500);
                            window.open(res.redirect_url, '_blank');
                            closeReorderModal();

                        } else {
                            notyf.error(res && res.message);
                        }
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
            });

            // Modal open/close logic
            const pendingPaymentBtn = document.getElementById('pendingPaymentBtn');
            const processPaymentModal = document.getElementById('processPaymentModal');

            function openProcessPaymentModal() {
                processPaymentModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                // Fresh token per modal-open — a double-click/retry of the
                // same submit reuses it (server treats as a duplicate); a
                // genuinely new payment attempt (re-opening the modal) gets
                // a new one.
                const tokenField = document.getElementById('paymentIdempotencyToken');
                if (tokenField) {
                    tokenField.value = (window.crypto && crypto.randomUUID)
                        ? crypto.randomUUID()
                        : 'idem-' + Date.now() + '-' + Math.random().toString(36).slice(2);
                }
            }

            function closeProcessPaymentModal() {
                processPaymentModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                // Reset partial payment UI
                document.getElementById('paymentModeFull').checked = true;
                document.getElementById('partialPaymentAmount').value = '';
                document.getElementById('remainingBalanceDisplay').textContent = '—';
                document.getElementById('partialAmountError').classList.add('hidden');
                // Restore original order context
                paymentTargetUniqueId = orderUniqueId;
                modalGrandTotal = parseFloat(processPaymentModal.dataset.grandTotal || '0');
                modalTotalPaid  = parseFloat(processPaymentModal.dataset.totalPaid  || '0');
                modalBalanceDue = Math.max(0, modalGrandTotal - modalTotalPaid);
                document.getElementById('balanceDueDisplay').textContent = fmtCurrency(modalBalanceDue);
                document.getElementById('processPaymentTitle').textContent = 'Process Payment';
                updatePaymentModeUI();
            }

            // === Full / Partial Payment toggle ===
            const paymentModeFull     = document.getElementById('paymentModeFull');
            const paymentModePartial  = document.getElementById('paymentModePartial');
            const partialPaymentFields = document.getElementById('partialPaymentFields');
            const partialPaymentAmountInput = document.getElementById('partialPaymentAmount');
            const amountOwedDisplay   = document.getElementById('amountOwedDisplay');
            const remainingBalanceDisplay = document.getElementById('remainingBalanceDisplay');
            const partialAmountError  = document.getElementById('partialAmountError');

            let modalGrandTotal = parseFloat(processPaymentModal.dataset.grandTotal || '0');
            let modalTotalPaid  = parseFloat(processPaymentModal.dataset.totalPaid  || '0');
            let modalBalanceDue = Math.max(0, modalGrandTotal - modalTotalPaid);
            let paymentTargetUniqueId = orderUniqueId;

            function fmtCurrency(n) {
                return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            function updatePaymentModeUI() {
                const isFull = paymentModeFull.checked;
                // Full label
                document.getElementById('labelFullPayment').classList.toggle('border-teal-500', isFull);
                document.getElementById('labelFullPayment').classList.toggle('bg-teal-50', isFull);
                document.getElementById('labelFullPayment').classList.toggle('border-gray-200', !isFull);
                document.getElementById('labelFullPayment').classList.toggle('bg-white', !isFull);
                document.getElementById('radioFullOuter').classList.toggle('border-teal-500', isFull);
                document.getElementById('radioFullOuter').classList.toggle('border-gray-300', !isFull);
                document.getElementById('radioFullDot').classList.toggle('hidden', !isFull);
                document.getElementById('labelFullPayment').querySelector('span:last-child').className =
                    'text-sm font-semibold ' + (isFull ? 'text-gray-800' : 'text-gray-500');
                // Partial label
                document.getElementById('labelPartialPayment').classList.toggle('border-teal-500', !isFull);
                document.getElementById('labelPartialPayment').classList.toggle('bg-teal-50', !isFull);
                document.getElementById('labelPartialPayment').classList.toggle('border-gray-200', isFull);
                document.getElementById('labelPartialPayment').classList.toggle('bg-white', isFull);
                document.getElementById('radioPartialOuter').classList.toggle('border-teal-500', !isFull);
                document.getElementById('radioPartialOuter').classList.toggle('border-gray-300', isFull);
                document.getElementById('radioPartialDot').classList.toggle('hidden', isFull);
                document.getElementById('labelPartialPayment').querySelector('span:last-child').className =
                    'text-sm font-semibold ' + (!isFull ? 'text-gray-800' : 'text-gray-500');
                // Partial fields
                partialPaymentFields.classList.toggle('hidden', isFull);
                if (!isFull) {
                    amountOwedDisplay.textContent = fmtCurrency(modalBalanceDue);
                    updateRemainingBalance();
                }
            }

            function updateRemainingBalance() {
                const payment   = parseFloat(partialPaymentAmountInput.value) || 0;
                const remaining = Math.max(0, modalBalanceDue - payment);
                remainingBalanceDisplay.textContent = fmtCurrency(remaining);
                if (payment > modalBalanceDue + 0.005) {
                    partialAmountError.textContent = 'Amount exceeds balance due (' + fmtCurrency(modalBalanceDue) + ')';
                    partialAmountError.classList.remove('hidden');
                } else if (payment <= 0 && partialPaymentAmountInput.value !== '') {
                    partialAmountError.textContent = 'Amount must be greater than 0';
                    partialAmountError.classList.remove('hidden');
                } else {
                    partialAmountError.classList.add('hidden');
                }
            }

            document.getElementById('labelFullPayment').addEventListener('click', function() {
                paymentModeFull.checked = true;
                updatePaymentModeUI();
            });
            document.getElementById('labelPartialPayment').addEventListener('click', function() {
                paymentModePartial.checked = true;
                updatePaymentModeUI();
            });
            partialPaymentAmountInput.addEventListener('input', updateRemainingBalance);

            // Initialize
            updatePaymentModeUI();
            document.getElementById('balanceDueDisplay').textContent = fmtCurrency(modalBalanceDue);

            // Open payment modal for an extension order (called from extension table buttons)
            window.openPaymentModalForExtension = function(uniqueId, grandTotal, totalPaid, orderNumber) {
                paymentTargetUniqueId = uniqueId;
                modalGrandTotal = grandTotal;
                modalTotalPaid  = totalPaid;
                modalBalanceDue = Math.max(0, grandTotal - totalPaid);
                document.getElementById('balanceDueDisplay').textContent = fmtCurrency(modalBalanceDue);
                document.getElementById('processPaymentTitle').textContent = 'Process Payment — ' + orderNumber;
                // Reset to Full Payment mode
                document.getElementById('paymentModeFull').checked = true;
                document.getElementById('partialPaymentAmount').value = '';
                document.getElementById('remainingBalanceDisplay').textContent = '—';
                document.getElementById('partialAmountError').classList.add('hidden');
                updatePaymentModeUI();
                openProcessPaymentModal();
            };

            // 👉 Open the modal when clicking the Pending Payment pill
            if (pendingPaymentBtn) {
                pendingPaymentBtn.addEventListener('click', openProcessPaymentModal);
            }

            // 👉 Close on Escape while open
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !processPaymentModal.classList.contains('hidden')) {
                    closeProcessPaymentModal();
                }
            });

            // 👉 (Optional) Close when clicking the backdrop
            processPaymentModal.addEventListener('click', (e) => {
                if (e.target === processPaymentModal) closeProcessPaymentModal();
            });

            document.querySelectorAll('.close-process-payment-modal-btn').forEach(btn => btn.addEventListener(
                'click',
                closeProcessPaymentModal));

            const paymentType = document.getElementById('payment_type');
            const creditCardOptions = document.getElementById('creditCardOptions');
            const cardOption = document.getElementById('cardOption');
            const newCardFields = document.getElementById('newCardFields');
            const cardOnFileDropdown = document.getElementById('cardOnFileDropdown');
            const cardNumberInput = document.getElementById('cardNumber');
            const expiryInput = document.getElementById('expiry');
            const cvcInput = document.getElementById('cvc');
            const chequeNumberField = document.getElementById('chequeNumberField');
            const paymentNoteLabel = document.getElementById('paymentNoteLabel');

            // "Other" carries no inherent meaning on its own — the note
            // becomes required so the record stays as accurate as every
            // named method (mirrors ReceivePaymentRequest's server-side rule).
            function syncPaymentNoteRequirement() {
                const isOther = paymentType.value === 'Other';
                paymentNoteLabel.textContent = isOther ? 'Describe Payment Method' : 'Notes (Optional)';
                paymentNoteLabel.classList.toggle('required', isOther);
            }

            // ===== Show/hide card sections =====
            function resetCreditCardFields() {
                cardOption.value = '';
                newCardFields.classList.add('hidden');
                cardOnFileDropdown.classList.add('hidden');
                document.getElementById('firstName').value = '';
                document.getElementById('lastName').value = '';
                cardNumberInput.value = '';
                expiryInput.value = '';
                cvcInput.value = '';
                document.getElementById('opaqueDataValue').value = '';
                document.getElementById('opaqueDataDescriptor').value = '';
            }

            paymentType.addEventListener('change', function() {
                if (this.value === 'CreditCard') {
                    resetCreditCardFields();
                    creditCardOptions.classList.remove('hidden');
                    chequeNumberField.classList.add('hidden');
                } else if (this.value === 'Cheque') {
                    resetCreditCardFields();
                    creditCardOptions.classList.add('hidden');
                    chequeNumberField.classList.remove('hidden');
                } else {
                    resetCreditCardFields();
                    creditCardOptions.classList.add('hidden');
                    chequeNumberField.classList.add('hidden');
                }
                syncPaymentNoteRequirement();
            });
            syncPaymentNoteRequirement();

            // ===== Input formatting =====
            cardNumberInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').substring(0, 16).replace(/(.{4})/g, '$1 ')
                    .trim();
            });

            expiryInput.addEventListener('input', function() {
                let val = this.value.replace(/[^0-9]/g, '').substring(0, 4);
                if (val.length >= 3) val = val.substring(0, 2) + '/' + val.substring(2);
                this.value = val;
            });

            cvcInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').substring(0, 4);
            });

            cardOption.addEventListener('change', function() {
                console.log("Card option changed:", this.value);
                if (this.value === 'NewCard') {
                    newCardFields.classList.remove('hidden');
                    cardOnFileDropdown.classList.add('hidden');
                } else if (this.value === 'CardOnFile') {
                    newCardFields.classList.add('hidden');
                    cardOnFileDropdown.classList.remove('hidden');
                } else {
                    newCardFields.classList.add('hidden');
                    cardOnFileDropdown.classList.add('hidden');
                }
            });

            document.getElementById('paymentForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = e.target;

                if (!$(form).parsley().isValid()) {
                    $(form).parsley().validate();
                    return;
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                const selectedPaymentMethod = paymentType.value;
                console.log("Selected payment method:", selectedPaymentMethod);
                let endpoint =
                    "{{ route('admin.order-management.orders.receive-payment', ':unique_id') }}";

                if (!selectedPaymentMethod) {
                    notyf.error('Please select a payment method.');
                    return;
                }
                endpoint = endpoint.replace(':unique_id', paymentTargetUniqueId);

                if (selectedPaymentMethod === 'CreditCard') {
                    const selectedCardOption = document.getElementById('cardOption').value;
                    if (selectedCardOption === 'NewCard') {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Saving...';
                        try {
                            // Card fields
                            const firstName = document.getElementById('firstName').value.trim();
                            const lastName = document.getElementById('lastName').value.trim();
                            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g,
                                '');
                            const expiry = document.getElementById('expiry').value.trim();
                            const cvc = document.getElementById('cvc').value.trim();

                            // Basic validation
                            function luhnCheck(num) {
                                let arr = (num + '').split('').reverse().map(x => parseInt(x));
                                let sum = arr.reduce((acc, val, idx) => {
                                    if (idx % 2) {
                                        val *= 2;
                                        if (val > 9) val -= 9;
                                    }
                                    return acc + val;
                                }, 0);
                                return sum % 10 === 0;
                            }

                            if (!firstName || !lastName) {
                                throw new Error('First name and last name are required.');
                            }

                            if (!/^\d{13,19}$/.test(cardNumber) || !luhnCheck(cardNumber)) {
                                throw new Error('Invalid or missing card number.');
                            }

                            if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                                throw new Error('Invalid or missing expiry date. Use MM/YY.');
                            }

                            const [mm, yy] = expiry.split('/');
                            const now = new Date();
                            const expiryYear = 2000 + parseInt(yy, 10);
                            const expiryMonth = parseInt(mm, 10);

                            if (
                                expiryMonth < 1 || expiryMonth > 12 ||
                                expiryYear < now.getFullYear() ||
                                (expiryYear === now.getFullYear() && expiryMonth < (now.getMonth() + 1))
                            ) {
                                throw new Error('Card expiry is in the past.');
                            }

                            if (!/^\d{3,4}$/.test(cvc)) {
                                throw new Error('Invalid or missing CVC code.');
                            }

                            // Tokenize with Accept.js
                            const [expMonth, expYearShort] = expiry.split('/');
                            const expYear = '20' + expYearShort;

                            const authData = {
                                clientKey: '{{ safe_decrypt($paymentSetting['payment_api_public_key']) }}',
                                apiLoginID: '{{ safe_decrypt($paymentSetting['payment_api_key']) }}'
                            };
                            const cardData = {
                                cardNumber: cardNumber,
                                month: expMonth,
                                year: expYear,
                                cardCode: cvc
                            };
                            const secureData = {
                                authData: authData,
                                cardData: cardData
                            };

                            Accept.dispatchData(secureData, function(response) {
                                if (response.messages.resultCode === "Error") {
                                    let errorMsg = response.messages.message.map(m => m.text).join(
                                        ', ');
                                    submitBtn.disabled = false;
                                    submitBtn.textContent = originalText;
                                    return notyf.error('Card Error: ' + errorMsg);
                                } else {
                                    document.getElementById('opaqueDataValue').value = response
                                        .opaqueData.dataValue;
                                    document.getElementById('opaqueDataDescriptor').value = response
                                        .opaqueData.dataDescriptor;

                                    // ✅ Proceed only after tokenization success
                                    processApi();
                                }
                            });
                        } catch (error) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                            notyf.error(error.message);
                            return;
                        }
                    } else {
                        processApi();
                    }
                } else {
                    processApi();
                }

                // Helper to send API request
                function processApi() {
                    const formData = new FormData(form);
                    formData.set('_method', 'PUT');
                    // Never send these:
                    formData.delete('cardNumber');
                    formData.delete('expiry');
                    formData.delete('cvc');
                    formData.delete('payment_mode'); // radio group — translated below

                    // Partial payment injection
                    if (paymentModePartial.checked) {
                        const partialAmt = parseFloat(partialPaymentAmountInput.value);
                        if (!partialAmt || partialAmt <= 0) {
                            notyf.error('Please enter a valid payment amount.');
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                            return;
                        }
                        if (partialAmt > modalBalanceDue + 0.005) {
                            notyf.error('Payment amount cannot exceed balance due (' + fmtCurrency(modalBalanceDue) + ').');
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                            return;
                        }
                        formData.set('partial_payment', '1');
                        formData.set('payment_amount', partialAmt.toFixed(2));
                    } else {
                        formData.delete('partial_payment');
                        formData.delete('payment_amount');
                    }
                    if (!submitBtn.disabled) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Saving...';
                    }

                    const cardNumber = form.querySelector('[name="cardNumber"]').value;
                    const expiry = form.querySelector('[name="expiry"]').value; // MM/YY

                    const last4 = cardNumber.replace(/\s+/g, '').slice(-4);

                    let expMonth = '';
                    let expYear = '';

                    if (expiry && expiry.includes('/')) {
                        [expMonth, expYear] = expiry.split('/');
                    }

                    if (expMonth && expYear && last4) {
                        // ✅ Safe card metadata (for dedupe / matching)
                        formData.set('card_number', last4);
                        formData.set('mm_yy', expMonth + '/' + expYear);
                    }


                    apiFetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: formData
                        })
                        .then(res => {
                            if (res && res.success) {
                                notyf.success(res.message);
                                window.location.reload();
                            } else {
                                notyf.error(res && res.message);
                            }
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        });
                }

            });

            // Modal open/close logic
            const refundPaymentBtn = document.getElementById('refundPaymentBtn');
            const refundModal = document.getElementById('refundModal');

            // Elements
            const orderId = refundModal.dataset.orderId;
            const customerName = refundModal.dataset.customerName;
            const originalAmount = parseFloat(refundModal.dataset.originalAmount || '0');
            const actionUrl = refundModal.dataset.action;
            const previewActionUrl = refundModal.dataset.previewAction;

            const refundFormStep = document.getElementById('refundFormStep');
            const refundConfirmStep = document.getElementById('refundConfirmStep');
            const amountInput = document.getElementById('refund_amount');
            const refundReasonInput = document.getElementById('refund_reason');
            const initiateBtn = document.getElementById('rf_btn_initiate');
            const refundFormStepBtnDiv = document.getElementById('refundFormStepBtnsDiv');
            const refundConfirmStepBtnDiv = document.getElementById('refundConfirmStepBtnsDiv');
            const confirmBtn = document.getElementById('rf_btn_confirm');
            const backBtn = document.getElementById('rf_btn_back');
            const errAmount = document.getElementById('rf_err_amount');
            const errReason = document.getElementById('rf_err_reason');
            const confirmAmount = document.getElementById('rf_c_amount');
            const confirmReason = document.getElementById('rf_c_reason');
            const confirmRemaining = document.getElementById('rf_c_remaining');
            const confirmRemainingDiv = document.getElementById('rf_c_remaining_row');


            // Type indicator
            const typeBox = document.getElementById('rf_type_box');
            const typeLabel = document.getElementById('rf_type_label');
            const typeText = document.getElementById('rf_type_text');
            const typeIcon = document.getElementById('rf_type_icon');

            // Helpers
            const fmt = (n) => '{{ config('app.currency.code') }}' + (n || 0).toFixed(2);
            const show = (el) => el.classList.remove('hidden');
            const hide = (el) => el.classList.add('hidden');

            // Special-refund context — server-verified numbers (the backend
            // re-checks all of this independently before processing).
            const calcTypeInput = document.getElementById('refund_calculation_type');
            const orderSubtotal = parseFloat(refundModal.dataset.orderSubtotal || '0');
            const orderTaxAmount = parseFloat(refundModal.dataset.orderTaxAmount || '0');

            const ccFeeEligible = refundModal.dataset.ccFeeEligible === '1';
            const ccFeePercentage = parseFloat(refundModal.dataset.ccFeePercentage || '0');
            const ccFeeRefundAmount = parseFloat(refundModal.dataset.ccFeeRefund || '0');
            const ccFeeRetainedAmount = parseFloat(refundModal.dataset.ccFeeRetained || '0');

            const taxOnlyEligible = refundModal.dataset.taxOnlyEligible === '1';
            const taxOriginal = parseFloat(refundModal.dataset.taxOriginal || '0');
            const taxAlreadyRefunded = parseFloat(refundModal.dataset.taxAlreadyRefunded || '0');
            const taxRemainingRefundable = parseFloat(refundModal.dataset.taxRemainingRefundable || '0');

            // ── Phase 3C — Refund Sources ───────────────────────────────
            const rfAmbiguous = refundModal.dataset.ambiguous === '1';
            const rfAllSources = JSON.parse(refundModal.dataset.sources || '[]');
            const sourcesBox = document.getElementById('rf_sources_box');
            const sourcesList = document.getElementById('rf_sources_list');
            const sourcesTotalEl = document.getElementById('rf_sources_total');
            const sourcesErrorEl = document.getElementById('rf_sources_error');
            const ambiguousBox = document.getElementById('rf_ambiguous_box');

            // Partial-failure recovery state (refinement: reallocate to a
            // different source, not only retry the same one). Populated by
            // enterRecoveryMode() after a partially-completed/failed
            // response; { [original_order_payment_id]: lockedAmount } for
            // every source that has ALREADY succeeded under the current
            // idempotency token — those rows render disabled and their
            // amount can never be edited away (they already happened).
            let recoveryMode = false;
            let lockedSourceAmounts = {};

            // Mirrors PaymentAllocationService::suggestAllocation() —
            // oldest eligible source first, until the requested amount is
            // fully allocated. Client-side only for responsiveness; the
            // server independently re-derives and validates the real
            // allocation on submit, never trusting this.
            function suggestAllocation(totalAmount, pool) {
                let remaining = Math.round(totalAmount * 100) / 100;
                const suggestions = {};
                for (const s of pool) {
                    if (remaining <= 0) break;
                    if (s.remaining <= 0) continue;
                    const take = Math.round(Math.min(s.remaining, remaining) * 100) / 100;
                    suggestions[s.id] = take;
                    remaining = Math.round((remaining - take) * 100) / 100;
                }
                return suggestions;
            }

            function sourceRowInputs() {
                return Array.from(sourcesList.querySelectorAll('.rf-source-amount'));
            }

            // The allocations array submitted to the server. For a
            // single-eligible-source order the table is never shown — the
            // one source is implicitly and fully allocated, so the
            // employee never has an extra step for the common case.
            function getAllocations() {
                if (rfAllSources.length <= 1) {
                    if (rfAllSources.length === 0) return [];
                    return [{
                        original_order_payment_id: rfAllSources[0].id,
                        amount: parseFloat(amountInput.value) || 0,
                    }];
                }

                return sourceRowInputs()
                    .map(input => ({
                        original_order_payment_id: parseInt(input.dataset.sourceId, 10),
                        amount: parseFloat(input.value) || 0,
                    }))
                    .filter(a => a.amount > 0);
            }

            function updateSourcesTotal() {
                if (rfAllSources.length <= 1) return true;

                const total = getAllocations().reduce((sum, a) => sum + a.amount, 0);
                const requested = parseFloat(amountInput.value) || 0;
                const balanced = Math.abs(Math.round((total - requested) * 100)) <= 1;

                sourcesTotalEl.textContent = 'Allocated: ' + fmt(total) + ' of ' + fmt(requested);
                sourcesTotalEl.className = 'text-xs font-medium ' + (balanced ? 'text-green-600' : 'text-amber-600');

                if (!balanced) {
                    sourcesErrorEl.textContent = 'The sources above must add up to exactly the refund total before this can be processed.';
                    show(sourcesErrorEl);
                } else {
                    hide(sourcesErrorEl);
                }

                return balanced;
            }

            function renderSources() {
                if (rfAmbiguous) {
                    show(ambiguousBox);
                    hide(sourcesBox);
                    initiateBtn.disabled = true;
                    return;
                }

                if (rfAllSources.length <= 1) {
                    hide(sourcesBox);
                    return;
                }

                show(sourcesBox);
                sourcesList.innerHTML = '';

                const requested = parseFloat(amountInput.value) || 0;
                const lockedIds = Object.keys(lockedSourceAmounts).map(Number);
                const lockedTotal = Object.values(lockedSourceAmounts).reduce((sum, v) => sum + v, 0);
                // In recovery mode only the NOT-YET-successful portion is
                // still up for a decision — locked sources are excluded
                // from both the suggestion pool and the amount being
                // suggested, so re-rendering never proposes changing money
                // that already moved.
                const toAllocate = recoveryMode ? Math.round((requested - lockedTotal) * 100) / 100 : requested;

                const restrictToCard = calcTypeInput.value === 'card_processing_fee_retained';
                const pool = (restrictToCard ? rfAllSources.filter(s => s.is_card && s.has_transaction) : rfAllSources)
                    .filter(s => !lockedIds.includes(s.id));
                const suggestion = suggestAllocation(toAllocate, pool);

                rfAllSources.forEach(function (s) {
                    const locked = lockedIds.includes(s.id);
                    const row = document.createElement('div');
                    row.className = 'flex items-center gap-2 border rounded-md px-3 py-2 text-sm '
                        + (locked ? 'border-green-200 bg-green-50' : 'border-gray-200');

                    const label = s.method + (s.masked ? ' ' + s.masked : '');
                    const suggested = locked ? lockedSourceAmounts[s.id] : (suggestion[s.id] || 0);
                    const badge = locked
                        ? '<span class="inline-block mt-0.5 text-[11px] font-medium text-green-700">&#10003; Completed — locked</span>'
                        : (recoveryMode ? '<span class="inline-block mt-0.5 text-[11px] font-medium text-amber-700">Needs a source</span>' : '');

                    row.innerHTML = `
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900 truncate">${label}</div>
                            <div class="text-[11px] text-gray-500">
                                Paid: ${fmt(s.amount)} &middot; Already Refunded: ${fmt(s.already_refunded)} &middot; Remaining: ${fmt(s.remaining)}
                            </div>
                            ${badge}
                        </div>
                        <div class="w-28">
                            <input type="text" inputmode="decimal"
                                data-source-id="${s.id}" data-max="${s.remaining}"
                                class="rf-source-amount w-full border border-gray-300 rounded-md px-2 py-1 text-sm text-right ${locked ? 'bg-gray-100 text-gray-500' : ''}"
                                value="${suggested > 0 ? suggested.toFixed(2) : ''}"
                                placeholder="0.00" ${locked ? 'disabled' : ''} />
                        </div>
                    `;
                    sourcesList.appendChild(row);
                });

                sourceRowInputs().forEach(input => {
                    input.addEventListener('input', function () {
                        const max = parseFloat(this.dataset.max || '0');
                        const val = parseFloat(this.value) || 0;
                        if (val > max) this.value = max.toFixed(2);
                        updateSourcesTotal();
                    });
                });

                updateSourcesTotal();
            }

            const bdBox = document.getElementById('rf_breakdown_box');
            const bdTitle = document.getElementById('rf_breakdown_title');
            const bdStandard = document.getElementById('rf_breakdown_standard');
            const bdCcFee = document.getElementById('rf_breakdown_cc_fee');
            const bdTaxOnly = document.getElementById('rf_breakdown_tax_only');
            const ccFeeBtn = document.getElementById('rf_cc_fee_btn');
            const taxOnlyBtn = document.getElementById('rf_tax_only_btn');

            function calcProportionalTax(refundAmount, subtotal, taxAmount) {
                if (refundAmount <= 0 || subtotal <= 0 || taxAmount <= 0) return 0;
                const rate = taxAmount / subtotal;
                return Math.round((refundAmount - (refundAmount / (1 + rate))) * 100) / 100;
            }

            function setActiveShortcut(activeBtn) {
                document.querySelectorAll('.refund-calc-btn, .refund-special-btn').forEach(b => {
                    b.classList.remove('ring-2', 'ring-blue-500');
                });
                if (activeBtn) activeBtn.classList.add('ring-2', 'ring-blue-500');
            }

            function updateBreakdown() {
                hide(bdStandard);
                hide(bdCcFee);
                hide(bdTaxOnly);
                hide(bdBox);

                const amt = parseFloat(amountInput.value) || 0;
                if (amt <= 0) return;

                const calcType = calcTypeInput.value;

                if (calcType === 'card_processing_fee_retained' && ccFeeEligible) {
                    show(bdBox);
                    show(bdCcFee);
                    bdTitle.textContent = 'Cancellation Refund';
                    document.getElementById('rf_bd_cc_refundable').textContent = fmt(ccFeeRefundAmount + ccFeeRetainedAmount);
                    document.getElementById('rf_bd_cc_fee_label').textContent =
                        'Card processing fee (' + ccFeePercentage.toFixed(2) + '%):';
                    document.getElementById('rf_bd_cc_fee').textContent = fmt(ccFeeRetainedAmount);
                    document.getElementById('rf_bd_cc_total').textContent = fmt(ccFeeRefundAmount);
                } else if (calcType === 'sales_tax_only' && taxOnlyEligible) {
                    show(bdBox);
                    show(bdTaxOnly);
                    bdTitle.textContent = 'Sales Tax Refund';
                    document.getElementById('rf_bd_tax_original').textContent = fmt(taxOriginal);
                    document.getElementById('rf_bd_tax_prev').textContent = fmt(taxAlreadyRefunded);
                    document.getElementById('rf_bd_tax_now').textContent = fmt(taxRemainingRefundable);
                } else if (orderTaxAmount > 0 && orderSubtotal > 0) {
                    show(bdBox);
                    show(bdStandard);
                    bdTitle.textContent = 'Partial Refund';
                    const taxPortion = calcProportionalTax(amt, orderSubtotal, orderTaxAmount);
                    const purchasePortion = Math.round((amt - taxPortion) * 100) / 100;
                    document.getElementById('rf_bd_purchase').textContent = fmt(purchasePortion);
                    document.getElementById('rf_bd_tax').textContent = fmt(taxPortion);
                    document.getElementById('rf_bd_total').textContent = fmt(amt);
                }

                renderSources();
            }

            // Enters recovery mode after a partially-completed or fully-
            // failed response: locks every already-succeeded source's row
            // (can't be un-charged, so it must never be editable again),
            // keeps the SAME idempotency token so the next submit is
            // recognized server-side as a retry of this exact event
            // (never a new refund), fixes the total requested amount, and
            // sends the employee back to the sources table to decide —
            // retry the failed source at the same amount, or move that
            // amount to a different eligible source entirely.
            //
            // A source flagged needs_manual_review (the gateway call
            // succeeded but the system couldn't record it — see
            // RefundPaymentController's documented persistence sequence)
            // is NEVER made retryable through this UI: automatically
            // resubmitting it could re-call the gateway for money that may
            // have already moved. When that happens the whole recovery
            // flow is blocked and the employee is told to contact support
            // instead, rather than being offered a "Retry" that could
            // double-refund.
            function enterRecoveryMode(sources) {
                const blocked = (sources || []).some(s => s.needs_manual_review);

                if (blocked) {
                    showRefundFormStep();
                    hide(sourcesBox);
                    hide(ambiguousBox);
                    initiateBtn.disabled = true;
                    const banner = document.getElementById('rf_recovery_banner');
                    banner.className = 'rounded-lg p-3 bg-red-50 border border-red-200 text-sm';
                    banner.innerHTML = '<p class="font-medium text-red-800">This refund needs manual review</p>'
                        + '<p class="text-red-700 mt-1">Part of this refund may have already succeeded at the payment gateway but could not be recorded. '
                        + 'Do not retry from this screen — contact support with this order number so it can be reconciled safely.</p>';
                    show(banner);
                    return;
                }

                recoveryMode = true;
                lockedSourceAmounts = {};
                (sources || []).forEach(function (s) {
                    if (s.success) lockedSourceAmounts[s.original_order_payment_id] = s.amount;
                });

                amountInput.disabled = true;
                initiateBtn.disabled = false;
                initiateBtn.textContent = 'Review Reallocation';
                const banner = document.getElementById('rf_recovery_banner');
                banner.className = 'rounded-lg p-3 bg-amber-50 border border-amber-200 text-sm';
                banner.innerHTML = '<p class="font-medium text-amber-800">Refund not yet complete</p>'
                    + '<p class="text-amber-700 mt-1">Retry the failed source below at the same amount, or move that amount to a different source, then resubmit. '
                    + 'Sources marked <span class="font-medium">Completed</span> are locked — they already succeeded and will not be charged again.</p>';
                show(banner);

                showRefundFormStep();
                renderSources();
            }

            function exitRecoveryMode() {
                recoveryMode = false;
                lockedSourceAmounts = {};
                amountInput.disabled = false;
                initiateBtn.disabled = false;
                initiateBtn.textContent = 'Initiate Refund';
                hide(document.getElementById('rf_recovery_banner'));
            }

            function openRefundModal() {
                refundModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                const tokenField = document.getElementById('refundIdempotencyToken');
                if (tokenField) {
                    tokenField.value = (window.crypto && crypto.randomUUID)
                        ? crypto.randomUUID()
                        : 'idem-' + Date.now() + '-' + Math.random().toString(36).slice(2);
                }
                // Reset special-refund state fresh each open — otherwise a
                // prior selection could linger if the modal was closed
                // without submitting.
                calcTypeInput.value = 'standard';
                setActiveShortcut(null);
                refundPaymentType.disabled = false;
                exitRecoveryMode();
                hide(document.getElementById('rf_results_box'));
                updateBreakdown();
            }

            function closeRefundModal() {
                refundModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            if (refundPaymentBtn) {
                refundPaymentBtn.addEventListener('click', openRefundModal);
            }

            // Phase 3C — "Resolve Refund" (Order Details incomplete-refund
            // banner): seeds recovery mode directly from server-rendered
            // data for the target refund event, WITHOUT a live submission
            // first — the employee lands straight on the sources table
            // with the same idempotency token, ready to retry the failed
            // source or reallocate it, exactly as if they'd just seen a
            // partial-failure response in this same session.
            const retryIncompleteBtn = document.getElementById('retryIncompleteRefundBtn');
            if (retryIncompleteBtn) {
                retryIncompleteBtn.addEventListener('click', function () {
                    const retry = JSON.parse(this.dataset.retry || 'null');
                    if (!retry) return;

                    refundModal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');

                    document.getElementById('refundIdempotencyToken').value = retry.token;
                    amountInput.value = retry.amount.toFixed(2);
                    amountInput.disabled = true;
                    calcTypeInput.value = retry.calc_type;
                    document.getElementById('refund_payment_type').value = retry.payment_type;
                    document.getElementById('refund_payment_type').dispatchEvent(new Event('change'));
                    setActiveShortcut(null);

                    enterRecoveryMode(retry.sources);
                });
            }

            // Phase 3D — Allocation Details "Retry / Reallocate Failed
            // Source" buttons: one per refund event, same seed-recovery-mode
            // flow as retryIncompleteBtn above, just delegated since there
            // can be more than one on the page.
            document.querySelectorAll('.allocation-detail-retry-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const retry = JSON.parse(this.dataset.retry || 'null');
                    if (!retry) return;

                    refundModal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');

                    document.getElementById('refundIdempotencyToken').value = retry.token;
                    amountInput.value = retry.amount.toFixed(2);
                    amountInput.disabled = true;
                    calcTypeInput.value = retry.calc_type;
                    document.getElementById('refund_payment_type').value = retry.payment_type;
                    document.getElementById('refund_payment_type').dispatchEvent(new Event('change'));
                    setActiveShortcut(null);

                    enterRecoveryMode(retry.sources);
                });
            });

            // Fallback when the target refund row has no idempotency token
            // to resume — starts an ordinary NEW refund pre-filled to the
            // outstanding amount rather than a true retry (see the blade
            // comment next to this button).
            const startNewForOutstandingBtn = document.getElementById('startNewRefundForOutstandingBtn');
            if (startNewForOutstandingBtn) {
                startNewForOutstandingBtn.addEventListener('click', function () {
                    openRefundModal();
                    const outstanding = parseFloat(this.dataset.outstanding || '0');
                    if (outstanding > 0) {
                        amountInput.value = outstanding.toFixed(2);
                        amountInput.dispatchEvent(new Event('input'));
                    }
                });
            }

            //  attach to BOTH close buttons
            document.querySelectorAll('.close-refund-modal-btn').forEach(btn => btn.addEventListener('click',
                closeRefundModal));

            // Payment Details modal — read-only, no form/idempotency token to manage.
            const paymentDetailsModal = document.getElementById('paymentDetailsModal');
            const paymentDetailsBtn = document.getElementById('paymentDetailsBtn');

            function openPaymentDetailsModal() {
                paymentDetailsModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closePaymentDetailsModal() {
                paymentDetailsModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            if (paymentDetailsBtn) {
                paymentDetailsBtn.addEventListener('click', openPaymentDetailsModal);
            }

            document.querySelectorAll('.close-payment-details-modal-btn').forEach(btn => btn.addEventListener('click',
                closePaymentDetailsModal));

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && paymentDetailsModal && !paymentDetailsModal.classList.contains('hidden')) {
                    closePaymentDetailsModal();
                }
            });

            if (paymentDetailsModal) {
                paymentDetailsModal.addEventListener('click', (e) => {
                    if (e.target === paymentDetailsModal) closePaymentDetailsModal();
                });
            }

            function updateTypeIndicator() {
                const amt = parseFloat(amountInput.value) || 0;
                if (amt <= 0) {
                    hide(typeBox);
                    return;
                }
                const remaining = originalAmount - amt;
                if (remaining < 0) {
                    hide(typeBox);
                    return;
                }
                const isFull = amt === originalAmount;
                show(typeBox);
                typeBox.className = 'rounded-lg p-3 ' + (isFull ? 'bg-red-50 border border-red-200' :
                    'bg-yellow-50 border border-yellow-200');
                typeLabel.className = 'text-sm font-medium ' + (isFull ? 'text-red-800' : 'text-yellow-800');
                typeText.className = 'text-xs mt-1 ' + (isFull ? 'text-red-700' : 'text-yellow-700');
                typeIcon.style.backgroundColor = isFull ? '#FEE2E2' : '#FEF9C3'; // red-100 / yellow-100
                typeIcon.style.border = '1px solid ' + (isFull ? '#FECACA' : '#FEF08A'); // red-200 / yellow-200
                typeIcon.style.color = isFull ? '#FCA5A5' : '#FDE047'; // red-300 / yellow-300
                typeLabel.textContent = isFull ? 'Full Refund' : 'Partial Refund';
                typeText.textContent = isFull ?
                    'The entire order amount will be refunded to the customer.' :
                    `${fmt(amt)} will be refunded. Remaining balance: ${fmt(originalAmount - amt)}`;
            }

            // Event handlers
            amountInput.addEventListener('input', function(e) {
                // A genuine keystroke (isTrusted) means the employee is
                // typing a custom amount — that's no longer "the system's"
                // fee/tax-only calculation, so fall back to Standard. The
                // shortcut buttons below also dispatch an 'input' event
                // programmatically (isTrusted === false) to trigger this
                // same listener without resetting the type they just set.
                if (e.isTrusted) {
                    calcTypeInput.value = 'standard';
                    setActiveShortcut(null);
                    refundPaymentType.disabled = false;
                }
                amountInput.value = amountInput.value;
                if (!errAmount.classList.contains('hidden')) hide(errAmount);
                updateTypeIndicator();
                updateBreakdown();
            });

            refundReasonInput.addEventListener('input', function() {
                if (!errReason.classList.contains('hidden')) hide(errReason);
            });

            // ===== Show/hide refund cheque field =====
            const refundPaymentType = document.getElementById('refund_payment_type');
            const refundChequeNumberField = document.getElementById('refund_cheque_number_field');
            const refundPaymentNoteField = document.getElementById('refund_payment_note_field');

            refundPaymentType.addEventListener('change', function() {
                refundChequeNumberField.classList.toggle('hidden', this.value !== 'Cheque');
                refundPaymentNoteField.classList.toggle('hidden', this.value !== 'Other');
            });

            function refundPaymentNoteValid() {
                if (refundPaymentType.value === 'Other' && !document.getElementById('refund_payment_note').value.trim()) {
                    notyf.error('Describe the payment method when "Other" is selected.');
                    return false;
                }
                return true;
            }

            // "Other" reason reveals its describe field
            const refundReasonOtherField = document.getElementById('refund_reason_other_field');
            refundReasonInput.addEventListener('change', function() {
                refundReasonOtherField.classList.toggle('hidden', this.value !== 'other');
            });

            // Processed By verification fields
            const refundProcessedBy   = document.getElementById('refund_processed_by');
            const refundEmployeeCode  = document.getElementById('refund_employee_code');
            const errProcessedBy      = document.getElementById('rf_err_processed_by');

            function refundProcessedByValid() {
                const ok = refundProcessedBy.value && refundEmployeeCode.value.trim();
                errProcessedBy.classList.toggle('hidden', !!ok);
                return !!ok;
            }

            function refundReasonOtherValid() {
                if (refundReasonInput.value === 'other' && !document.getElementById('refund_reason_other').value.trim()) {
                    notyf.error('Describe the reason when "Other" is selected.');
                    return false;
                }
                return true;
            }

            function refundReasonDisplay() {
                const label = refundReasonInput.selectedOptions.length ?
                    refundReasonInput.selectedOptions[0].text : '';
                const other = document.getElementById('refund_reason_other').value.trim();
                return refundReasonInput.value === 'other' && other ? label + ' — ' + other : label;
            }

            function renderRefundResults(data) {
                const box = document.getElementById('rf_results_box');
                const sources = data.sources || [];
                const opStatus = data.refund_operation_status;

                if (opStatus === 'completed' || !sources.length) {
                    hide(box);
                    hide(document.getElementById('rf_recovery_banner'));
                    return;
                }

                const succeeded = sources.filter(s => s.success);
                const needsReview = sources.filter(s => !s.success && s.needs_manual_review);
                const retryable = sources.filter(s => !s.success && !s.needs_manual_review);
                const outstanding = typeof data.outstanding_amount === 'number' ? data.outstanding_amount : null;

                let html = '';
                if (opStatus === 'failed') {
                    box.className = 'rounded-lg p-4 border space-y-2 text-sm bg-red-50 border-red-200';
                    html += '<p class="font-medium text-red-800">Refund failed — none of the selected sources could be refunded.</p>';
                } else {
                    box.className = 'rounded-lg p-4 border space-y-2 text-sm bg-amber-50 border-amber-200';
                    html += '<p class="font-medium text-amber-800">Refund partially completed</p>';
                }

                // The outstanding DOLLAR AMOUNT leads — never just the
                // status label (Phase 3C refinement).
                if (outstanding !== null) {
                    html += `<p class="text-sm"><span class="font-semibold">${fmt(outstanding)}</span> still unresolved.</p>`;
                }

                if (succeeded.length) {
                    html += '<div><p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mt-2">Completed</p>';
                    succeeded.forEach(s => {
                        html += `<div class="flex justify-between text-gray-800"><span>${s.method}</span><span class="font-medium">${fmt(s.amount)}</span></div>`;
                    });
                    html += '</div>';
                }
                if (retryable.length) {
                    html += '<div><p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mt-2">Failed — retry below or reallocate to another source</p>';
                    retryable.forEach(s => {
                        html += `<div class="flex justify-between text-gray-800"><span>${s.method}</span><span class="font-medium">${fmt(s.amount)}</span></div>`;
                        if (s.failure_reason) {
                            html += `<div class="text-xs text-gray-500">Reason: ${s.failure_reason}</div>`;
                        }
                    });
                    html += '</div>';
                }
                if (needsReview.length) {
                    html += '<div><p class="text-xs font-semibold text-red-700 uppercase tracking-wide mt-2">Needs manual review — do not retry</p>';
                    needsReview.forEach(s => {
                        html += `<div class="flex justify-between text-gray-800"><span>${s.method}</span><span class="font-medium">${fmt(s.amount)}</span></div>`;
                        html += `<div class="text-xs text-red-600">${s.failure_reason || 'Contact support for reconciliation.'}</div>`;
                    });
                    html += '</div>';
                }

                box.innerHTML = html;
                show(box);
            }

            function submitRefund() {
                const form = document.getElementById('refundForm');

                if (!$(form).parsley().isValid()) {
                    $(form).parsley().validate();
                    return;
                }

                const amt = parseFloat(amountInput.value) || 0;
                if (amt <= 0 || amt > originalAmount) {
                    notyf.error('Please enter a valid refund amount.');
                    return;
                }

                const reason = refundReasonInput.value.trim();
                if (!reason) {
                    notyf.error('Please select a refund reason.');
                    return;
                }

                if (!refundReasonOtherValid() || !refundPaymentNoteValid() || !refundProcessedByValid()) {
                    return;
                }

                // Phase 3C — explicit source allocation. Built the same way
                // for a fresh submit and a retry: retry simply resubmits
                // the identical set under the SAME idempotency token
                // (never regenerated on retry — see the retry button
                // handler below), and the server skips whatever already
                // succeeded rather than re-charging it.
                const allocations = getAllocations();
                if (!allocations.length) {
                    notyf.error('Select at least one refund source.');
                    return;
                }
                if (!updateSourcesTotal()) {
                    notyf.error('The refund sources must add up to the refund total.');
                    return;
                }

                const submitBtn = document.getElementById('rf_btn_confirm');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';

                apiFetch(actionUrl, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            amount: amt,
                            reason: reason,
                            reason_other: document.getElementById('refund_reason_other').value.trim() || null,
                            processed_by: refundProcessedBy.value,
                            employee_code: refundEmployeeCode.value.trim(),
                            payment_type: document.getElementById('refund_payment_type').value,
                            cheque_number: document.getElementById('refund_cheque_number')
                                .value || null,
                            payment_note: document.getElementById('refund_payment_note')
                                .value.trim() || null,
                            refund_calculation_type: calcTypeInput.value,
                            idempotency_token: document.getElementById('refundIdempotencyToken').value || null,
                            allocations: allocations,
                        })
                    })
                    .then(res => {
                        if (!res) {
                            notyf.error('An unexpected error occurred. Please try again.');
                            return;
                        }

                        renderRefundResults(res);

                        if (res.refund_operation_status === 'completed') {
                            exitRecoveryMode();
                            notyf.success(res.message);
                            window.location.reload();
                        } else if (res.refund_operation_status === 'partially_completed' || res.refund_operation_status === 'failed') {
                            notyf.error(res.message);
                            // Send the employee back to the sources table:
                            // successful sources lock in place, the failed
                            // one(s) stay editable so they can retry the
                            // same source or reallocate the outstanding
                            // amount to a different one entirely — never
                            // only a bare "retry the same thing" loop.
                            enterRecoveryMode(res.sources);
                        } else {
                            const firstError = res.errors ? res.errors[0] : null;
                            notyf.error(firstError || res.message);
                        }
                    })
                    .catch(() => {
                        notyf.error('An unexpected error occurred. Please try again.');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        if (submitBtn.textContent === 'Processing...') {
                            submitBtn.textContent = originalText;
                        }
                    });
            }

            document.getElementById('refundForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitRefund();
            });

            const refundCalcBtn = document.getElementsByClassName('refund-calc-btn');
            Array.from(refundCalcBtn).forEach(btn => {
                btn.addEventListener('click', function() {
                    calcTypeInput.value = 'standard';
                    setActiveShortcut(btn);
                    refundPaymentType.disabled = false;
                    amountInput.value = (originalAmount * (btn.dataset.percentage / 100)).toFixed(
                        2);
                    amountInput.dispatchEvent(new Event('input'));
                });
            });

            if (ccFeeBtn && ccFeeEligible) {
                ccFeeBtn.addEventListener('click', function() {
                    calcTypeInput.value = 'card_processing_fee_retained';
                    setActiveShortcut(ccFeeBtn);
                    // The fee is only meaningful if the refund actually goes
                    // back to the card — lock the dropdown so it can't drift.
                    refundPaymentType.value = 'CreditCard';
                    refundPaymentType.dispatchEvent(new Event('change'));
                    refundPaymentType.disabled = true;
                    // The source table needs the GROSS total to draw down
                    // (what reduces each card's remaining balance) — the
                    // customer-facing NET (ccFeeRefundAmount) is shown
                    // separately in the breakdown/confirm steps, never
                    // submitted as the field the sources must sum to.
                    amountInput.value = (ccFeeRefundAmount + ccFeeRetainedAmount).toFixed(2);
                    amountInput.dispatchEvent(new Event('input'));
                });
            }

            if (taxOnlyBtn && taxOnlyEligible) {
                taxOnlyBtn.addEventListener('click', function() {
                    calcTypeInput.value = 'sales_tax_only';
                    setActiveShortcut(taxOnlyBtn);
                    refundPaymentType.disabled = false;
                    amountInput.value = taxRemainingRefundable.toFixed(2);
                    amountInput.dispatchEvent(new Event('input'));
                });
            }

            initiateBtn.addEventListener('click', function() {
                // Handle the initiate refund button click

                const amt = parseFloat(amountInput.value) || 0;
                const reason = refundReasonInput.value.trim();

                if (amt <= 0 || amt > originalAmount) {
                    show(errAmount);
                    return;
                }

                if (!reason) {
                    show(errReason);
                    return;
                }

                if (!refundReasonOtherValid() || !refundPaymentNoteValid() || !refundProcessedByValid()) {
                    return;
                }

                if (rfAmbiguous) {
                    notyf.error('This order has a legacy refund that cannot be attributed — refunds are blocked.');
                    return;
                }

                if (!getAllocations().length || !updateSourcesTotal()) {
                    notyf.error('The refund sources must add up to the refund total before continuing.');
                    return;
                }

                // If all validations pass, proceed with the refund
                showConfirmationStep();
            });

            function showConfirmationStep() {
                hide(refundFormStep);
                show(refundConfirmStep);
                hide(refundFormStepBtnDiv);
                show(refundConfirmStepBtnDiv);
                confirmAmount.textContent = amountInput.value;
                confirmReason.textContent = refundReasonDisplay();
                confirmRemaining.textContent = (originalAmount - parseFloat(amountInput.value)).toFixed(2);
                if (parseFloat(confirmRemaining.textContent) <= 0) {
                    hide(confirmRemainingDiv);
                } else {
                    show(confirmRemainingDiv);
                }

                const calcTypeLabels = {
                    'card_processing_fee_retained': 'Full Amount Less Card Processing Fee',
                    'sales_tax_only': 'Sales Tax Only',
                };
                const calcTypeRow = document.getElementById('rf_c_calc_type_row');
                const calcTypeLabel = calcTypeLabels[calcTypeInput.value];
                if (calcTypeLabel) {
                    document.getElementById('rf_c_calc_type').textContent = calcTypeLabel;
                    show(calcTypeRow);
                } else {
                    hide(calcTypeRow);
                }

                // ── Phase 3C — sources + tax/fee/net preview ────────────
                // A best-effort PREVIEW only, built from the same simple
                // proportional-tax helper the standard breakdown box
                // already used pre-Phase-3C — never the authoritative
                // per-source split algorithm (that lives exclusively in
                // PaymentAllocationService::calculateAllocationSplits() on
                // the server, which recomputes and validates everything
                // independently before anything is charged).
                const allocations = getAllocations();
                const sourcesBoxC = document.getElementById('rf_c_sources_box');
                const sourcesListC = document.getElementById('rf_c_sources_list');

                if (rfAllSources.length > 1 && allocations.length) {
                    // "Remaining Refundable After This Refund" per source —
                    // src.remaining is this payment's CURRENT remaining
                    // refundable balance (server-computed, unaffected by
                    // this not-yet-submitted request); subtracting what's
                    // about to be drawn from it previews the balance it
                    // will have once this refund actually processes.
                    //
                    // Skipped for a LOCKED source (recovery mode, already
                    // succeeded on a prior attempt of this same event):
                    // src.remaining is a snapshot from when the modal
                    // opened and does not reflect that already-recorded
                    // success, so previewing "after" from it here would be
                    // wrong — a "Completed" note is shown instead.
                    sourcesListC.innerHTML = allocations.map(a => {
                        const src = rfAllSources.find(s => s.id === a.original_order_payment_id);
                        const label = src ? (src.method + (src.masked ? ' ' + src.masked : '')) : ('Payment #' + a.original_order_payment_id);
                        const locked = a.original_order_payment_id in lockedSourceAmounts;
                        const after = (!locked && src) ? Math.max(0, Math.round((src.remaining - a.amount) * 100) / 100) : null;
                        return `
                            <div class="flex justify-between text-gray-800">
                                <span>${label}</span>
                                <span class="font-medium">${fmt(a.amount)}</span>
                            </div>
                            ${locked ? `<div class="text-[11px] text-green-600 -mt-0.5 mb-1">Already completed on a prior attempt</div>` : ''}
                            ${after !== null ? `<div class="flex justify-between text-[11px] text-gray-500 -mt-0.5 mb-1">
                                <span>Remaining Refundable After This Refund</span>
                                <span>${fmt(after)}</span>
                            </div>` : ''}
                        `;
                    }).join('');
                    show(sourcesBoxC);
                } else {
                    hide(sourcesBoxC);
                }

                // ── Phase 3D — server-authoritative preview ─────────────
                // Replaces the client-side estimate above with real numbers
                // from PaymentAllocationService (via RefundPaymentPreviewController)
                // the moment they arrive — no gateway call, no write, same
                // validation the real submission runs. The client render
                // above stays as the instant placeholder while this is in
                // flight so the confirm step never looks empty.
                fetch(previewActionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({
                            amount: parseFloat(amountInput.value) || 0,
                            payment_type: document.getElementById('refund_payment_type').value,
                            refund_calculation_type: calcTypeInput.value,
                            idempotency_token: document.getElementById('refundIdempotencyToken').value || null,
                            allocations: allocations,
                        }),
                    })
                    .then(res => res.json())
                    .then(preview => {
                        if (!preview.success || !preview.rows) return;

                        sourcesListC.innerHTML = preview.rows.map(r => `
                            <div class="flex justify-between text-gray-800">
                                <span>${r.method}</span>
                                <span class="font-medium">${fmt(r.refund_now)}</span>
                            </div>
                            <div class="text-[11px] text-gray-500 -mt-0.5 mb-1 space-y-0.5">
                                <div class="flex justify-between"><span>Original payment</span><span>${fmt(r.original_amount)}</span></div>
                                <div class="flex justify-between"><span>Previously refunded</span><span>${fmt(r.previously_refunded)}</span></div>
                                <div class="flex justify-between"><span>Remaining before</span><span>${fmt(r.remaining_before)}</span></div>
                                ${r.fee_retained > 0 ? `<div class="flex justify-between"><span>Fee retained</span><span>${fmt(r.fee_retained)}</span></div>` : ''}
                                ${r.already_completed
                                    ? `<div class="text-green-600">Already completed on a prior attempt</div>`
                                    : `<div class="flex justify-between"><span>Remaining after</span><span>${fmt(r.remaining_after)}</span></div>`}
                            </div>
                        `).join('');
                        show(sourcesBoxC);
                    })
                    .catch(() => {
                        // Preview is a best-effort upgrade over the client
                        // estimate already rendered above — a network
                        // failure here leaves that estimate in place rather
                        // than blocking the confirm step.
                    });

                const taxRow = document.getElementById('rf_c_tax_row');
                const purchaseRow = document.getElementById('rf_c_purchase_row');
                const feeRow = document.getElementById('rf_c_fee_row');
                const netRow = document.getElementById('rf_c_net_row');
                hide(taxRow); hide(purchaseRow); hide(feeRow); hide(netRow);

                if (calcTypeInput.value === 'card_processing_fee_retained') {
                    document.getElementById('rf_c_fee').textContent = fmt(ccFeeRetainedAmount);
                    document.getElementById('rf_c_net').textContent = fmt(ccFeeRefundAmount);
                    show(feeRow); show(netRow);
                } else if (orderTaxAmount > 0 && orderSubtotal > 0) {
                    const taxPortion = calcProportionalTax(amt, orderSubtotal, orderTaxAmount);
                    const purchasePortion = Math.round((amt - taxPortion) * 100) / 100;
                    document.getElementById('rf_c_tax').textContent = fmt(taxPortion);
                    document.getElementById('rf_c_purchase').textContent = fmt(purchasePortion);
                    show(taxRow); show(purchaseRow);
                }
            }

            function showRefundFormStep() {
                hide(refundConfirmStep);
                show(refundFormStep);
                show(refundFormStepBtnDiv);
                hide(refundConfirmStepBtnDiv);
            }


            backBtn.addEventListener('click', function() {
                showRefundFormStep();
            });

        });

        // ── Void Payment ──────────────────────────────────────────────────────────
        (function () {
            const voidBtn    = document.getElementById('voidPaymentBtn');
            const voidModal  = document.getElementById('voidModal');
            const confirmBtn = document.getElementById('confirmVoidBtn');

            if (!voidBtn || !voidModal) return;

            const voidUrl    = voidBtn.dataset.url;
            const voidAmount = parseFloat(voidBtn.dataset.amount || '0').toFixed(2);
            const voidOrderPaymentId = voidBtn.dataset.orderPaymentId;
            document.getElementById('void_display_amount').textContent = '$' + voidAmount;

            function openVoid()  { voidModal.classList.remove('hidden'); }
            function closeVoid() { voidModal.classList.add('hidden'); }

            voidBtn.addEventListener('click', openVoid);
            voidModal.querySelectorAll('.void-modal-close').forEach(el => el.addEventListener('click', closeVoid));
            voidModal.addEventListener('click', function (e) { if (e.target === voidModal) closeVoid(); });

            // Processed By verification + reason fields
            const voidReason        = document.getElementById('void_reason');
            const voidReasonOther   = document.getElementById('void_reason_other');
            const voidReasonOtherEl = document.getElementById('void_reason_other_field');
            const voidProcessedBy   = document.getElementById('void_processed_by');
            const voidEmployeeCode  = document.getElementById('void_employee_code');

            voidReason.addEventListener('change', function () {
                voidReasonOtherEl.classList.toggle('hidden', this.value !== 'other');
            });

            confirmBtn.addEventListener('click', function () {
                if (!voidReason.value) {
                    if (window.notyf) notyf.error('Please select a reason for the void.');
                    return;
                }
                if (voidReason.value === 'other' && !voidReasonOther.value.trim()) {
                    if (window.notyf) notyf.error('Describe the reason when "Other" is selected.');
                    return;
                }
                if (!voidProcessedBy.value || !voidEmployeeCode.value.trim()) {
                    if (window.notyf) notyf.error('Select the employee and enter their Employee ID.');
                    return;
                }

                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Processing…';

                fetch(voidUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        order_payment_id: voidOrderPaymentId,
                        reason: voidReason.value,
                        reason_other: voidReasonOther.value.trim() || null,
                        processed_by: voidProcessedBy.value,
                        employee_code: voidEmployeeCode.value.trim(),
                        // Explicit disposition — only a deliberate "cancel"
                        // choice closes the schedule; a plain void never does.
                        cancel_order: document.getElementById('void_disposition_cancel')?.checked === true,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closeVoid();
                        if (window.notyf) notyf.success(data.message || 'Payment voided successfully.');
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        const firstError = data && data.errors ? Object.values(data.errors)[0][0] : null;
                        if (window.notyf) notyf.error(firstError || data.message || 'Void failed.');
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Yes, Void Payment';
                    }
                })
                .catch(() => {
                    if (window.notyf) notyf.error('An unexpected error occurred. Please try again.');
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Yes, Void Payment';
                });
            });
        })();

        // document.getElementById('send-terms').addEventListener('click', function() {
        //     const btn = this;
        //     btn.disabled = true;

        //     const endpoint = btn.dataset.url;

        //     apiFetch(endpoint, {
        //             method: 'POST',
        //             headers: {
        //                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
        //                     .getAttribute('content')
        //             }
        //         })
        //         .then(res => {
        //             if (res && res.success) {
        //                 notyf.success(res.message);
        //             } else {
        //                 notyf.error(res && res.message);
        //             }
        //         })
        //         .finally(() => {
        //             btn.disabled = false;
        //         });
        // });

        const sendTermsBtn = document.getElementById('send-terms');

        if (sendTermsBtn) {
            sendTermsBtn.addEventListener('click', function() {
                const btn = this;
                btn.disabled = true;

                const endpoint = btn.dataset.url;

                apiFetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content')
                        }
                    })
                    .then(res => {
                        if (res && res.success) {
                            notyf.success(res.message);
                        } else {
                            notyf.error(res?.message || 'Something went wrong');
                        }
                    })
                    .catch(() => {
                        notyf.error('Request failed');
                    })
                    .finally(() => {
                        btn.disabled = false;
                    });
            });
        }

        // ── Resend POD Payment Link ──────────────────────────────────────────
        const resendPodBtn = document.getElementById('resendPodPaymentLinkBtn');

        if (resendPodBtn) {
            resendPodBtn.addEventListener('click', function () {
                const btn      = this;
                const icon     = btn.querySelector('.btn-icon');
                const label    = btn.querySelector('.btn-label');
                const spinner  = btn.querySelector('.btn-spinner');

                btn.disabled = true;
                icon?.classList.add('hidden');
                spinner?.classList.remove('hidden');
                label.textContent = 'Sending…';

                apiFetch(btn.dataset.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                })
                .then(res => {
                    if (res && res.success) {
                        notyf.success('Payment link sent successfully.');
                        // Update "last sent" label under the button
                        let sub = btn.closest('.flex.flex-col')?.querySelector('.text-xs');
                        if (!sub) {
                            sub = document.createElement('div');
                            sub.className = 'text-xs text-gray-500 mt-1 ml-[2px] text-center';
                            btn.closest('.flex.flex-col')?.appendChild(sub);
                        }
                        sub.textContent = 'Last sent ' + (res.sent_at ?? 'just now');
                    } else {
                        notyf.error(res?.message || 'Failed to send payment link.');
                    }
                })
                .catch(() => notyf.error('Request failed. Please try again.'))
                .finally(() => {
                    btn.disabled = false;
                    icon?.classList.remove('hidden');
                    spinner?.classList.add('hidden');
                    label.textContent = 'Resend Payment Link';
                });
            });
        }
    </script>

    <script>
        // Equipment Assignment Modal Logic
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('equipmentAssignModal');
            const equipmentAssignForm = document.getElementById('equipmentAssignForm');
            const categorySelect = document.getElementById('category_select');
            const equipmentSelect = document.getElementById('equipment_unique_id');
            const assignBtn = document.getElementById('equipment-assign-submit');
            const statusDisplayId = 'equipment-status-display';
            const equipmentPageLinkId = 'equipment-page-link';

            let fullData = @json($categories);
            let currentOrderProductUniqueId = null;
            let currentScheduleType = null;
            let softAssignedEquipmentData = null;
            let previousStatusValue = null;
            let statusFieldElement = null;

            // Close modal handlers
            document.querySelectorAll('.close-equipment-assign-modal').forEach(btn => {
                btn.addEventListener('click', function() {
                    modal.classList.add('hidden');

                    // Revert status back to pending if modal was cancelled
                    if (previousStatusValue !== null && statusFieldElement) {
                        statusFieldElement.value = 'Pending';
                        statusFieldElement.setAttribute('data-previous-value', 'Pending');
                        updateScheduleField(currentScheduleType.toLowerCase(), currentScheduleType
                            .toLowerCase() + '_status', 'Pending');
                    }

                    clearModalFields();
                });
            });

            // Radio button toggle
            document.getElementById('useCurrentlyAssigned')?.addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('newAssignmentFields').classList.add('opacity-50',
                        'pointer-events-none');
                } else {
                    document.getElementById('newAssignmentFields').classList.remove('opacity-50',
                        'pointer-events-none');
                }
            });

            document.getElementById('assignNew')?.addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('newAssignmentFields').classList.remove('opacity-50',
                        'pointer-events-none');
                }
            });

            // Populate categories
            fullData.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.title;
                categorySelect.appendChild(opt);
            });

            // Category change handler
            categorySelect.addEventListener('change', function() {
                const selectedCatId = Number(this.value);
                equipmentSelect.innerHTML = '<option value="">Select Equipment</option>';

                let equipments = [];

                if (!selectedCatId) {
                    fullData.forEach(cat => {
                        if (Array.isArray(cat.equipments)) {
                            equipments = equipments.concat(cat.equipments);
                        }
                    });
                } else {
                    const category = fullData.find(c => c.id === selectedCatId);
                    if (!category) return;
                    equipments = category.equipments || [];
                }

                if (equipments.length === 0) {
                    const opt = document.createElement('option');
                    opt.textContent = 'No equipments';
                    opt.disabled = true;
                    opt.selected = true;
                    equipmentSelect.appendChild(opt);
                    updateEquipmentStatus();
                    return;
                }

                // Group by status
                const groups = {
                    maintenance: [],
                    rented: [],
                    damaged: [],
                    available: [],
                    other: []
                };

                equipments.forEach(equipment => {
                    const status = (equipment.current_status || '').toLowerCase();
                    if (status === 'available') groups.available.push(equipment);
                    else if (status === 'rented') groups.rented.push(equipment);
                    else if (status === 'damaged') groups.damaged.push(equipment);
                    else if (status === 'maintenance') groups.maintenance.push(equipment);
                    else groups.other.push(equipment);
                });

                appendGroup('Available', groups.available);
                appendGroup('Maint. Hold', groups.maintenance);
                appendGroup('Damaged', groups.damaged);
                appendGroup('Rented', groups.rented);
                appendGroup('Other', groups.other);

                updateEquipmentStatus();
            });

            function appendGroup(label, list) {
                if (list.length === 0) return;
                const group = document.createElement('optgroup');
                group.label = label;

                list.forEach(equipment => {
                    const opt = document.createElement('option');
                    opt.value = equipment.unique_id;
                    opt.textContent = `${equipment.equipment_name} (${equipment.equipment_id})`;
                    opt.setAttribute('data-current-status', equipment.current_status?.toLowerCase() || '');
                    opt.setAttribute('data-link',
                        `/admin/maintenance-management/equipment/${equipment.unique_id}/edit`);
                    opt.setAttribute('data-link-title', equipment.equipment_name);
                    group.appendChild(opt);
                });

                equipmentSelect.appendChild(group);
            }

            function updateEquipmentStatus() {
                const statusDiv = document.getElementById(statusDisplayId);
                const pageLink = document.getElementById(equipmentPageLinkId);

                if (!equipmentSelect || !statusDiv || !assignBtn || !pageLink) return;

                const selectedOption = equipmentSelect.options[equipmentSelect.selectedIndex] || {};
                const status = selectedOption.getAttribute?.('data-current-status');
                const link = selectedOption.getAttribute?.('data-link') || '';
                const title = selectedOption.getAttribute?.('data-link-title') || '';

                if (!status) {
                    statusDiv.textContent = '';
                    statusDiv.className = 'text-sm font-semibold text-gray-600';
                    assignBtn.disabled = true;
                    pageLink.href = '';
                    pageLink.textContent = '';
                    return;
                }

                let statusText = '';
                let statusColor = 'text-gray-600';
                let isAvailable = true;

                switch (status) {
                    case 'available':
                        statusText = 'Available';
                        statusColor = 'text-green-600';
                        isAvailable = true;
                        break;
                    case 'rented':
                        statusText = 'Rented';
                        statusColor = 'text-gray-600';
                        isAvailable = true;
                        break;
                    case 'damaged':
                        statusText = 'Not Available';
                        statusColor = 'text-red-600';
                        isAvailable = true;
                        break;
                    case 'maintenance':
                        statusText = 'Maint. Hold';
                        statusColor = 'text-yellow-600';
                        isAvailable = true;
                        break;
                    default:
                        statusText = status || '';
                        statusColor = 'text-gray-600';
                        isAvailable = true;
                }

                statusDiv.textContent = `Status: ${statusText}`;
                statusDiv.className = `text-sm font-semibold ${statusColor}`;
                assignBtn.disabled = !isAvailable;
                pageLink.href = link;
                pageLink.textContent = title;
            }

            equipmentSelect.addEventListener('change', updateEquipmentStatus);

            // Form submit
            equipmentAssignForm?.addEventListener('submit', function(e) {
                e.preventDefault();

                const assignmentType = document.querySelector('input[name="assignment_type"]:checked')
                    ?.value;
                let equipmentUniqueId = null;

                if (assignmentType === 'current') {
                    // Use soft assigned equipment
                    if (softAssignedEquipmentData) {
                        equipmentUniqueId = softAssignedEquipmentData.unique_id;
                    }
                } else {
                    // Use newly selected equipment
                    equipmentUniqueId = equipmentSelect.value;
                }

                if (!equipmentUniqueId) {
                    if (window.notyf) notyf.error('Please select an equipment.');
                    return;
                }

                const submitBtn = document.getElementById('equipment-assign-submit');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Assigning...';
                }

                const formData = new FormData();
                formData.append('order_product_unique_id', currentOrderProductUniqueId);
                formData.append('equipment_unique_id', equipmentUniqueId);
                formData.append('schedule_type', currentScheduleType);

                apiFetch('{{ route('admin.order-management.orders.assign-equipment') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(data => {
                        if (data?.success) {
                            modal.classList.add('hidden');
                            if (window.notyf) notyf.success(data.message ||
                                'Equipment assigned successfully');
                            clearModalFields();
                            // Reload page to show updated equipment
                            window.location.reload();
                        } else {
                            if (window.notyf) notyf.error(data?.message || 'Something went wrong.');
                        }
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Save';
                        }
                    });
            });

            function clearModalFields() {
                categorySelect.selectedIndex = 0;
                equipmentSelect.innerHTML = '<option value="">Select Equipment</option>';
                document.getElementById('order-product-unique-id').value = '';
                document.getElementById('schedule-type').value = '';
                document.getElementById('assignNew').checked = true;
                document.getElementById('useCurrentlyAssignedSection').classList.add('hidden');
                document.getElementById('newAssignmentFields').classList.remove('opacity-50',
                    'pointer-events-none');
                currentOrderProductUniqueId = null;
                currentScheduleType = null;
                softAssignedEquipmentData = null;
                previousStatusValue = null;
                statusFieldElement = null;
            }

            // Function to open equipment assignment modal
            window.openEquipmentAssignModal = function(orderProductUniqueId, scheduleType, softAssignment = null) {
                currentOrderProductUniqueId = orderProductUniqueId;
                currentScheduleType = scheduleType;

                // Store the status field element and its current value for potential revert
                const statusFieldId = scheduleType.toLowerCase() + '_status_' + orderProductUniqueId;
                statusFieldElement = document.getElementById(statusFieldId);
                if (statusFieldElement) {
                    previousStatusValue = statusFieldElement.value;
                }

                // Set order information
                document.getElementById('assign-order-id').textContent = '{{ $order->order_number }}';
                document.getElementById('assign-customer-name').textContent = '{{ $order->customer_name }}';

                // Find the order product
                const orderProducts = @json($order->products);
                const orderProduct = orderProducts.find(op => op.unique_id === orderProductUniqueId);

                if (orderProduct) {
                    document.getElementById('assign-product-name').textContent = orderProduct.product_name ||
                        '-';
                }

                document.getElementById('order-product-unique-id').value = orderProductUniqueId;
                document.getElementById('schedule-type').value = scheduleType;

                // Handle soft assignment
                if (softAssignment && softAssignment.equipment) {
                    softAssignedEquipmentData = softAssignment.equipment;
                    document.getElementById('useCurrentlyAssignedSection').classList.remove('hidden');
                    document.getElementById('soft-assigned-equipment').textContent = softAssignment.equipment
                        .equipment_name || '-';
                    document.getElementById('soft-assigned-id').textContent = softAssignment.equipment
                        .equipment_id || '-';
                } else {
                    document.getElementById('useCurrentlyAssignedSection').classList.add('hidden');
                    document.getElementById('assignNew').checked = true;
                }

                modal.classList.remove('hidden');
            };
        });
    </script>

    <script>
        function openAllMedia(orderProductId, type) {
            const modal = document.getElementById('allMediaModal');
            const content = document.getElementById('allMediaContent');

            const jsonEl = document.getElementById(`media-${orderProductId}-${type}`);
            if (!jsonEl) {
                content.innerHTML = `<p class="text-gray-500">No media found for this product.</p>`;
                modal.classList.remove('hidden');
                return;
            }

            let mediaData = [];
            try {
                mediaData = JSON.parse(jsonEl.textContent);
            } catch (e) {
                console.error("Invalid JSON", e);
            }

            let html = '';
            mediaData.forEach(m => {
                if (!m.url) return;
                let base = type ? type.toLowerCase() : '';
                let side = m.side ? m.side.toLowerCase() : '';

                let title = '';

                // License case
                if (base.includes('license')) {
                    if (side === 'front') {
                        title = 'Front Side of License';
                    } else if (side === 'back') {
                        title = 'Back Side of License';
                    } else {
                        title = 'License';
                    }
                }
                // Other types
                else if (base) {
                    title = base.charAt(0).toUpperCase() + base.slice(1);
                }
                // Fallback
                else {
                    title = 'Media';
                }


                // Video
                if (m.url.match(/\.(mp4|mov|webm)$/)) {
                    html += `
                        <div class="relative group bg-black rounded-lg overflow-hidden shadow-md">
                            <video class="w-full h-48 object-cover" muted>
                                <source src="${m.url}" type="video/mp4">
                            </video>
                            <a href="${m.url}"
                                class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition">
                                <!-- Play Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm-2 6.5l6 3.5-6 3.5v-7z"/>
                                </svg>
                            </a>
                        </div>
                    `;
                    }

                // Image
                else if (m.url.match(/\.(jpeg|jpg|png|gif|webp)$/)) {
                    html += `
              <div class="group bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden border border-gray-200">

                    <!-- Image -->
                    <div class="relative">
                        <img src="${m.url}" class="w-full h-48 object-cover" />

                        <!-- Overlay -->
                        <a href="${m.url}"
                            class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M10 2a8 8 0 105.293 14.293l4.707 4.707 1.414-1.414-4.707-4.707A8 8 0 0010 2zm0 2a6 6 0 110 12A6 6 0 0110 4z"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Title -->
                    <div class="px-3 py-2">
                        <p class="text-sm font-medium text-gray-800 truncate">
                            ${title}
                        </p>
                    </div>
                </div>
            `;
                }
            });

            content.innerHTML = html || `<p class="text-gray-500">No media available.</p>`;
            modal.classList.remove('hidden');
        }

        function closeAllMedia() {
            const modal = document.getElementById('allMediaModal');
            const content = document.getElementById('allMediaContent');
            modal.classList.add('hidden');
            content.innerHTML = ''; // cleanup
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.receipt-action').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.classList.contains('processing')) {
                        e.preventDefault();
                        return;
                    }

                    e.preventDefault();
                    this.classList.add('processing');

                    const loader = this.querySelector('.loader-svg');
                    if (loader) {
                        loader.classList.remove('hidden');
                        const icon = this.querySelector('svg:not(.loader-svg)');
                        if (icon) icon.classList.add('hidden');
                    }

                    // Wait for next repaint
                    requestAnimationFrame(() => {
                        const href = this.getAttribute('href');

                        // Links marked target="_blank" (Print Receipt) open the
                        // inline PDF in a new tab so the user prints from the
                        // browser's viewer — this tab never navigates, so the
                        // spinner/processing lock must be reset here or the
                        // button stays stuck forever. Other receipt actions
                        // (Email Receipt) keep the original same-tab navigation.
                        if (this.getAttribute('target') === '_blank') {
                            window.open(href, '_blank', 'noopener,noreferrer');
                            setTimeout(() => {
                                this.classList.remove('processing');
                                const spin = this.querySelector('.loader-svg');
                                if (spin) {
                                    spin.classList.add('hidden');
                                    const icn = this.querySelector('svg:not(.loader-svg)');
                                    if (icn) icn.classList.remove('hidden');
                                }
                            }, 1500);
                            return;
                        }

                        window.location.href = href;
                    });
                });
            });
        });
    </script>

    <script>
    const currentUserId = {{ auth()->id() }};
</script>

    <script>
        document.addEventListener('click', function (e) {

            const btn = e.target.closest('.edit-po-btn');
            if (!btn) return;

            const input = document.getElementById('po_id');
            if (!input) return;

            const orderId = input.dataset.orderId;
            const poId = input.value.trim();

            if (!orderId) return;

            showConfirm('Update PO ID?', 'This will update the order PO ID.').then((result) => {
                if (!result.isConfirmed) return;

                apiFetch('{{ route('admin.order-management.orders.update-po-id') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        po_id: poId
                    })
                })
                .then(res => {
                    if (res && res.success) {
                        notyf.success(res.message || 'PO ID updated successfully.');
                    } else {
                        notyf.error(res && res.message ? res.message : 'Failed to update PO ID.');
                    }
                })
                .catch(() => {
                    notyf.error('Failed to update PO ID.');
                });

            });

        });

        // ── Change Return Date Modal ──────────────────────────────────────────
        (function () {
            const parseScheduleDate = window.parseScheduleDate || function(str) {
                if (!str) return null;
                const parts = str.split('/');
                if (parts.length !== 3) return null;
                const [m, d, y] = parts.map(Number);
                if (!m || !d || !y) return null;
                return new Date(y, m - 1, d);
            };
            const formatScheduleDate = window.formatScheduleDate || function(date) {
                if (!(date instanceof Date) || isNaN(date)) return '';
                return String(date.getMonth() + 1).padStart(2, '0') + '/' +
                       String(date.getDate()).padStart(2, '0') + '/' +
                       date.getFullYear();
            };

            window.parseScheduleDate = parseScheduleDate;
            window.formatScheduleDate = formatScheduleDate;
            const modal                   = document.getElementById('changeReturnDateModal');
            const optionDateOnly          = document.getElementById('returnDateOnly');
            const optionDateHours         = document.getElementById('returnDateAndHours');
            const hoursSection            = document.getElementById('returnHoursOptions');
            const currentAllocatedDisplay = document.getElementById('returnCurrentAllocatedHours');
            const totalDisplay            = document.getElementById('returnTotalHoursAdded');
            const grandTotalDisplay       = document.getElementById('returnGrandTotalHours');
            const customInput             = document.getElementById('customHoursInput');
            const saveBtn                 = document.getElementById('saveChangeReturnDate');
            const manualDateInput         = document.getElementById('returnManualDateInput');
            const manualDateIconBtn       = document.getElementById('returnManualDateIconBtn');
            const currentDateLabel        = document.getElementById('returnCurrentDateLabel');
            const cancelBtns              = [
                document.getElementById('closeChangeReturnDateModal'),
                document.getElementById('cancelChangeReturnDate'),
            ];

            let _orderProductId       = null;
            let _currentReturnDateValue = null; // current return date string for calculation
            let _deliveryDateValue    = null;   // fallback when current return date is unavailable
            let _updateFn             = null;
            let _returnDateEl         = null;   // DOM element to update on save
            let _currentAllocatedHours = 0;     // hours already on the order (equation's left term)

            // Calendar days added per duration type × 1 qty
            const DURATION_DAYS = { daily: 1, weekend: 1, weekly: 7, monthly: 28 };

            // qty state per type
            const qty = { daily: 1, weekend: 1, weekly: 1, monthly: 1 };

            // ── Helpers ──────────────────────────────────────────────────────
            function getSelectedHoursType() {
                return document.querySelector('input[name="hoursType"]:checked')?.value ?? 'daily';
            }

            function getBaseHours(type) {
                const radio = document.querySelector(`input[name="hoursType"][value="${type}"]`);
                return radio ? parseFloat(radio.dataset.hours) : 0;
            }

            // One shared formatter for all three equation values: "8" not
            // "8.00", "5.6" not "5.60", strips float noise (5.6000001), 0 → "0".
            function formatHours(n) {
                const num = parseFloat(n);
                if (!isFinite(num)) return '0';
                return String(Math.round(num * 1000) / 1000);
            }

            // Renders the live equation "Allocated + Added = Total" and returns
            // the ADDED hours — the value the save path submits (unchanged). The
            // grand total is display-only; the server still owns validation and
            // hour allocation.
            function recalcTotal() {
                const type = getSelectedHoursType();
                let added;
                if (type === 'custom') {
                    added = parseFloat(customInput.value) || 0;
                } else {
                    added = getBaseHours(type) * (qty[type] ?? 1);
                }
                const allocated = _currentAllocatedHours || 0;
                if (currentAllocatedDisplay) currentAllocatedDisplay.textContent = formatHours(allocated);
                totalDisplay.textContent = formatHours(added);
                if (grandTotalDisplay) grandTotalDisplay.textContent = formatHours(allocated + added);
                return added;
            }

            function updateQtyDisplay(type) {
                document.querySelectorAll(`.hours-qty-display[data-type="${type}"]`).forEach(el => {
                    el.textContent = qty[type];
                });
            }

            /**
             * Calculate the new return date from the current return date + type × qty days.
             * Returns a formatted string (MM/dd/yyyy) or '' on failure.
             */
            function calcReturnDate(baseDateStr, type, q) {
                const base = parseScheduleDate(baseDateStr);
                if (!base) return '';
                const days    = (DURATION_DAYS[type] || 1) * (q || 1);
                const newDate = new Date(base.getTime() + days * 86400000);
                return formatScheduleDate(newDate);
            }

            function getReturnDateCalcBase() {
                return _currentReturnDateValue || _deliveryDateValue || '';
            }

            /**
             * Sync the "New Date" display in the summary bar.
             * - Duration modes: populate the date field with the calculated date.
             * - "date only" or "custom": leave the date field as the user's manual selection.
             */
            function updateDateDisplay() {
                const type       = getSelectedHoursType();
                const dateOnly   = optionDateOnly.checked;
                const isManual   = dateOnly || (type === 'custom');

                if (!isManual) {
                    const newDate = calcReturnDate(getReturnDateCalcBase(), type, qty[type]);
                    manualDateInput.value = newDate || '';
                    if (_manualPicker && newDate) {
                        const parsed = parseScheduleDate(newDate);
                        if (parsed) _manualPicker.selectDate(parsed, { silent: true });
                    }
                }
            }

            // Toggle hours section visibility + update date display
            function syncHoursSection() {
                if (optionDateHours.checked) {
                    hoursSection.classList.remove('hidden');
                } else {
                    hoursSection.classList.add('hidden');
                }
                updateDateDisplay();
            }

            optionDateOnly.addEventListener('change', syncHoursSection);
            optionDateHours.addEventListener('change', syncHoursSection);

            // Initialise AirDatepicker on the manual date input inside the modal.
            // Deferred to DOMContentLoaded: window.AirDatepicker is set by the
            // Vite-bundled module script, which (per the HTML spec) only runs
            // after the document has finished parsing — i.e. after this classic
            // inline <script> block has already executed. Checking
            // window.AirDatepicker synchronously here was always false, so the
            // picker was never constructed at all until this was fixed.
            //
            // container is required: without it, AirDatepicker mounts its
            // calendar in a global container appended directly to <body>
            // (confirmed in the compiled bundle — buildGlobalContainer() does
            // document.body.appendChild(...)), completely outside this modal's
            // DOM tree. That global container's CSS z-index defaults to 100
            // (--adp-z-index), which loses to the modal's z-[99999], so the
            // calendar rendered behind the modal wherever they overlapped.
            // Setting container to the modal makes the calendar a descendant
            // of it instead, so it stacks within the modal's own context
            // rather than competing against the modal's own z-index.
            // (Note: AirDatepicker has no "zIndex" constructor option — it
            // does not exist in the library; do not reintroduce it.)
            let _manualPicker = null;
            document.addEventListener('DOMContentLoaded', function () {
                if (window.AirDatepicker && manualDateInput) {
                    _manualPicker = new window.AirDatepicker(manualDateInput, {
                        locale:     window.airDatepickerLocaleEn,
                        dateFormat: '{{ config('app.date.js_date_format') }}',
                        autoClose:  true,
                        container:  '#changeReturnDateModal',
                        onSelect({ formattedDate }) {
                            manualDateInput.value = formattedDate || '';
                        },
                    });
                }
            });

            // Calendar icon also opens the picker (in addition to clicking the input)
            if (manualDateIconBtn) {
                manualDateIconBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (_manualPicker) {
                        _manualPicker.show();
                    } else {
                        manualDateInput.focus();
                    }
                });
            }

            // Hours type radios
            document.querySelectorAll('.hours-type-radio').forEach(radio => {
                radio.addEventListener('change', function () {
                    customInput.classList.toggle('hidden', this.value !== 'custom');
                    recalcTotal();
                    updateDateDisplay();
                });
            });

            // Qty buttons
            document.querySelectorAll('.hours-qty-minus, .hours-qty-plus').forEach(btn => {
                btn.addEventListener('click', function () {
                    const type  = this.dataset.type;
                    const delta = this.classList.contains('hours-qty-plus') ? 1 : -1;
                    qty[type]   = Math.max(1, (qty[type] ?? 1) + delta);
                    // Select the corresponding radio
                    const radio = document.querySelector(`input[name="hoursType"][value="${type}"]`);
                    if (radio) { radio.checked = true; customInput.classList.add('hidden'); }
                    updateQtyDisplay(type);
                    recalcTotal();
                    updateDateDisplay();  // recalculate and show new date
                });
            });

            // Custom hours input
            customInput.addEventListener('input', recalcTotal);

            // Close handlers
            cancelBtns.forEach(btn => btn?.addEventListener('click', closeModal));
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });

            function closeModal() {
                modal.classList.add('hidden');
                _orderProductId         = null;
                _currentReturnDateValue = null;
                _deliveryDateValue      = null;
                _updateFn               = null;
                _returnDateEl           = null;
            }

            // ── Save ─────────────────────────────────────────────────────────
            saveBtn.addEventListener('click', function () {
                if (!_updateFn || !_orderProductId) return;

                const fn          = _updateFn;
                const updateHours = optionDateHours.checked;
                const totalHours  = updateHours ? recalcTotal() : 0;
                const returnEl    = _returnDateEl;

                // Determine the final date value
                const type = getSelectedHoursType();
                let dateValue;
                if (optionDateOnly.checked || type === 'custom') {
                    dateValue = manualDateInput.value;
                } else {
                    dateValue = calcReturnDate(getReturnDateCalcBase(), type, qty[type]);
                }

                if (!dateValue) {
                    notyf.error('Please select a return date.');
                    return;
                }

                closeModal();

                // Update the return date field on screen
                if (returnEl) {
                    returnEl.value = dateValue;
                    returnEl.setAttribute('data-previous-value', dateValue);
                    if (returnEl._airDatepicker) {
                        const parsed = parseScheduleDate(dateValue);
                        if (parsed) returnEl._airDatepicker.selectDate(parsed, { silent: true });
                    }
                }

                // Always save the date
                const datePromise = fn('return', 'pickup_date', dateValue);

                if (updateHours) {
                    Promise.resolve(datePromise).then(() => {
                        fn('return', 'allocated_hours', totalHours);
                    });
                }
            });

            // ── Public opener ─────────────────────────────────────────────────
            window.openChangeReturnDateModal = function (
                orderProductId,
                currentReturnDate,
                deliveryDate,
                updateScheduleField,
                currentAllocatedHours = 0,
                returnDateEl = null
            ) {
                _orderProductId         = orderProductId;
                _currentReturnDateValue = currentReturnDate;
                _deliveryDateValue      = deliveryDate;
                _updateFn               = updateScheduleField;
                _returnDateEl           = returnDateEl;

                // Show current date in summary bar
                if (currentDateLabel) {
                    currentDateLabel.textContent = currentReturnDate || '—';
                }
                _currentAllocatedHours = parseFloat(currentAllocatedHours) || 0;
                if (currentAllocatedDisplay) {
                    currentAllocatedDisplay.textContent = formatHours(_currentAllocatedHours);
                }

                // Pre-populate manual picker with current return date
                if (manualDateInput) {
                    manualDateInput.value = currentReturnDate || '';
                    if (_manualPicker && currentReturnDate) {
                        const parsed = parseScheduleDate(currentReturnDate);
                        if (parsed) _manualPicker.selectDate(parsed, { silent: true });
                    }
                }

                // Reset qty
                Object.keys(qty).forEach(k => { qty[k] = 1; updateQtyDisplay(k); });
                // Default: "date & hours" checked, daily selected
                optionDateHours.checked = true;
                document.getElementById('hoursTypeDaily').checked = true;
                customInput.classList.add('hidden');
                customInput.value = '';
                syncHoursSection();
                recalcTotal();
                updateDateDisplay();   // show calculated date for Daily ×1

                modal.classList.remove('hidden');
            };
        })();


    // ── Fuel Charge Alert from Order ─────────────────────────────────────────
    (function () {
        const alertChargeUrl = "{{ route('admin.order-management.orders.alert-charge', $order->unique_id) }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function openModal(id) {
            const el = document.getElementById(id);
            el.classList.remove('hidden');
            el.classList.add('flex');
        }
        function closeModal(id) {
            const el = document.getElementById(id);
            el.classList.remove('flex');
            el.classList.add('hidden');
        }

        function submitAlertCharge(type, amountId, personId, notesId, btnId) {
            const amount = document.getElementById(amountId).value.trim();
            const person = document.getElementById(personId).value;
            const notes  = document.getElementById(notesId).value.trim();

            if (!amount || parseFloat(amount) <= 0) {
                notyf.error('Please enter a valid amount.');
                return;
            }
            if (!person) {
                notyf.error('Please select a person responsible.');
                return;
            }

            const radioName = type === 'fuel' ? 'orderFuelSalesTax' : 'orderDamageSalesTax';
            const taxRadio = document.querySelector(`input[name="${radioName}"]:checked`);
            const salesTaxType = taxRadio ? taxRadio.value : 'free';

            const btn     = document.getElementById(btnId);
            const btnText = btn.querySelector('span') ?? btn;
            const spinner = btn.querySelector('svg');
            btn.disabled = true;
            if (btnText instanceof HTMLSpanElement) btnText.textContent = 'Saving...';
            else btn.textContent = 'Saving...';
            if (spinner) spinner.classList.remove('hidden');

            fetch(alertChargeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ type, amount, responsible_person: person, notes, sales_tax_type: salesTaxType }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    notyf.success(data.message);
                    closeModal('orderDamageAlertModal');
                    // Reload so the Billing Engine block reflects the new charge immediately.
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    notyf.error(data.message || 'Something went wrong.');
                }
            })
            .catch(err => { console.error('Alert charge error:', err); notyf.error('Request failed. Please try again.'); })
            .finally(() => {
                btn.disabled = false;
                if (btnText instanceof HTMLSpanElement) btnText.textContent = type === 'fuel' ? 'Add Charge' : 'Add Alert';
                else btn.textContent = type === 'fuel' ? 'Add Charge' : 'Add Alert';
                if (spinner) spinner.classList.add('hidden');
            });
        }

        // Fuel creation moved to the shared New Fuel Charge modal
        // (Billing Engine commonization) — only Damage still posts here.
        document.getElementById('orderDamageSubmitBtn').addEventListener('click', () =>
            submitAlertCharge('damage', 'orderDamageAmount', 'orderDamagePerson', 'orderDamageNotes', 'orderDamageSubmitBtn'));
    })();

    // ── Billing Engine — Charge Actions ──────────────────────────────────────
    (function () {
        const csrfToken      = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const beCurrentOrderId = {{ $order->id }};

        // Modal open/close for the page-specific View Damage modal.
        window.beCloseModal = function(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.remove('flex'); el.classList.add('hidden'); }
        };

        function beOpenModal(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.remove('hidden'); el.classList.add('flex'); }
        }

        // Saved cards for this order's customer — used by the shared
        // payment modal's extension pay-now step.
        window.beCustomerCards = @json($order->customer?->cards?->map(fn($c) => ['id' => $c->unique_id, 'label' => $c->card_number])->values() ?? []);

        // Payment collection, notes, adjust, resolve, uncollectible,
        // history, and refund are ALL handled by the shared charge-action
        // bundle (admin/charges/_action_modals) + shared payment modal
        // (window.BillingPayment) — this page only supplies its two
        // page-specific actions through the ChargeActions hook.
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.ChargeActions) return;
            window.ChargeActions.onAction = function (action, row) {
                if (action === 'view-damage') window.beOpenViewDamage(row);
                if (action === 'delete')      window.beOpenDelete(row);
            };
            // The shared bundle is included near the top of this page and
            // bound before the Billing Engine table exists in the DOM —
            // rebind now that every row is rendered.
            window.ChargeActions.bind();
        });

        @if (session('be_reopen_charge'))
        // A payment attempt failed on this charge — put the employee straight
        // back into the Collect Payment step with the failure reason visible.
        (function () {
            const row = document.querySelector('[data-bc-id="{{ session('be_reopen_charge') }}"]');
            if (!row || !window.BillingPayment) return;
            const label = (row.dataset.customerName || '')
                + (row.dataset.orderNumber ? ' · ' + row.dataset.orderNumber : '');
            window.BillingPayment.openForBillingRow(row.dataset, label);
            @if (session('error'))
                window.BillingPayment.showError(@json(session('error')));
            @endif
        })();
        @endif

        window.beOpenDelete = function(row) {
            const childNumber = row.dataset.childNumber;
            const typeLabels = { extension: 'Order Enhancement', fuel: 'Fuel Charge', damage: 'Damage Charge' };
            window.extDeleteFlow.open({
                contextHtml: 'Deleting this Order Enhancement will also remove '
                    + (childNumber
                        ? 'child order <span class="font-semibold">#' + childNumber + '</span>'
                        : 'its linked child order')
                    + '.<br><span class="font-semibold text-red-700">Both records will be affected.</span>',
                // Structured identification for the confirmation modal
                summary: {
                    type: typeLabels[row.dataset.type] || row.dataset.type,
                    customer: row.dataset.customerName,
                    order: row.dataset.orderNumber
                        + (childNumber ? ' (child #' + childNumber + ')' : ''),
                    amount: '$' + Number(row.dataset.amountTotal || 0).toFixed(2),
                },
                paystate: row.dataset.paystate,
                url: beRouteDelete.replace('__ID__', row.dataset.bcId),
                payload: {},
                onSuccess: function (data) {
                    notyf.success(data.message || 'Extension transaction deleted.');
                    setTimeout(() => window.location.reload(), 1500);
                },
            });
        };

        window.beOpenViewDamage = function(row) {
            const modal   = document.getElementById('beViewDamageModal');
            const loading = document.getElementById('beViewDamageLoading');
            const empty   = document.getElementById('beViewDamageEmpty');
            const tbody   = document.getElementById('beViewDamageTbody');

            tbody.innerHTML = '';
            empty.classList.add('hidden');
            loading.classList.remove('hidden');
            beOpenModal('beViewDamageModal');

            // ExtraChargesShowController expects the Order's integer PK
            const url = '{{ route('admin.dashboard.extra-charges.show', ':id') }}'.replace(':id', beCurrentOrderId);
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');
                const rows = data?.checklist?.rows || [];
                const hasDamage = data?.damage?.final > 0;
                if (!rows.length && !hasDamage) { empty.classList.remove('hidden'); return; }
                rows.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b';
                    tr.innerHTML = `
                        <td class="py-2 px-2 text-xs">${row.item ?? ''}</td>
                        <td class="py-2 px-2 text-xs">${row.delivered ?? 'Admin Override'}</td>
                        <td class="py-2 px-2 text-xs">${row.returned ?? '—'}</td>
                        <td class="py-2 px-2 text-xs text-right text-red-600">$${parseFloat(row.amount ?? 0).toFixed(2)}</td>
                    `;
                    tbody.appendChild(tr);
                });
                if (hasDamage) {
                    const tr = document.createElement('tr');
                    tr.className = 'border-t-2 font-semibold';
                    tr.innerHTML = `
                        <td class="py-2 px-2 text-xs" colspan="3">Total Damage</td>
                        <td class="py-2 px-2 text-xs text-right text-red-700">$${parseFloat(data.damage.final).toFixed(2)}</td>
                    `;
                    tbody.appendChild(tr);
                }
            })
            .catch(() => { loading.classList.add('hidden'); empty.classList.remove('hidden'); });
        };

        const beRouteDelete = '{{ route('admin.order-management.orders.billing-charges.delete', '__ID__') }}';
    })();

    // ── Extension Charges ──────────────────────────────────────────────────────
    (function () {
        const extTaxRate = {{ (float) ($sales_tax ?? 0) }};
        const extModal   = document.getElementById('extensionChargeModal');

        // ── Saved custom descriptions (localStorage) ──────────────────────────
        const EXT_DESC_KEY     = 'kabba_ext_charge_descriptions';
        const extDescSelect    = document.getElementById('extDescSelect');
        const extCustomGroup   = document.getElementById('extCustomDescGroup');
        const extCustomInput   = document.getElementById('extCustomDesc');
        const extSaveDescBtn   = document.getElementById('extSaveDescBtn');

        function getSavedDescs() {
            try { return JSON.parse(localStorage.getItem(EXT_DESC_KEY) || '[]'); }
            catch (e) { return []; }
        }

        function saveSavedDescs(arr) {
            localStorage.setItem(EXT_DESC_KEY, JSON.stringify(arr));
        }

        function buildDescOptions() {
            // Remove any previously injected custom options (before the "Other…" option)
            Array.from(extDescSelect.options).forEach(opt => {
                if (opt.dataset.custom === '1') opt.remove();
            });
            const saved = getSavedDescs();
            const otherOpt = extDescSelect.querySelector('option[value="__other__"]');
            saved.forEach(desc => {
                const opt = document.createElement('option');
                opt.value = desc;
                opt.textContent = desc;
                opt.dataset.custom = '1';
                extDescSelect.insertBefore(opt, otherOpt);
            });
        }

        // Initial population
        buildDescOptions();

        extDescSelect.addEventListener('change', function () {
            const isOther = this.value === '__other__';
            extCustomGroup.classList.toggle('hidden', !isOther);
            if (isOther) extCustomInput.focus();
            else extCustomInput.value = '';
        });

        extSaveDescBtn.addEventListener('click', function () {
            const text = extCustomInput.value.trim();
            if (!text) { notyf.error('Please type a description first.'); return; }

            const saved = getSavedDescs();
            if (saved.includes(text)) {
                notyf.error('That description is already in the list.');
                return;
            }

            saved.push(text);
            saveSavedDescs(saved);
            buildDescOptions();

            // Select the newly saved option and hide the custom input area
            extDescSelect.value = text;
            extCustomGroup.classList.add('hidden');
            extCustomInput.value = '';
            notyf.success('Description saved to list.');
        });

        // Derive the final description string from the select/custom input
        function getExtDescription() {
            if (extDescSelect.value === '__other__') {
                return extCustomInput.value.trim();
            }
            return extDescSelect.value.trim();
        }

        function fmtExtCurrency(v) {
            return '$' + Number(v).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function updateExtSummary() {
            const base   = parseFloat(document.getElementById('extBaseAmount').value) || 0;
            const addTax = document.getElementById('extTaxAdd').checked;
            const tax    = addTax ? Math.round(base * extTaxRate * 100) / 100 : 0;
            document.getElementById('extSummaryBase').textContent  = fmtExtCurrency(base);
            document.getElementById('extSummaryTax').textContent   = fmtExtCurrency(tax);
            document.getElementById('extSummaryTotal').textContent = fmtExtCurrency(base + tax);
        }

        // Duplicate-click guard: one key per modal open — the server refuses
        // a second create carrying the same key.
        let extRequestUuid = '';

        function openExtensionModal() {
            extRequestUuid = (window.crypto && crypto.randomUUID) ? crypto.randomUUID()
                : 'ext-' + Date.now() + '-' + Math.random().toString(36).slice(2);
            extModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeExtensionModal() {
            extModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            extDescSelect.value = '';
            extCustomGroup.classList.add('hidden');
            extCustomInput.value = '';
            document.getElementById('extBaseAmount').value  = '';
            document.getElementById('extTaxAdd').checked    = true;
            document.getElementById('extNotes').value       = '';
            document.getElementById('extPerson').value      = '';
            ['extDescError','extAmountError','extPersonError'].forEach(id => {
                document.getElementById(id).classList.add('hidden');
            });
            updateExtSummary();
        }

        window.openExtensionModal  = openExtensionModal;
        window.closeExtensionModal = closeExtensionModal;

        document.getElementById('extBaseAmount').addEventListener('input', updateExtSummary);
        document.querySelectorAll('input[name="extTaxTreatment"]').forEach(r => r.addEventListener('change', updateExtSummary));
        document.getElementById('closeExtensionModalX').addEventListener('click', closeExtensionModal);
        document.getElementById('closeExtensionModalBtn').addEventListener('click', closeExtensionModal);

        // Shared creation path. payNow=false → create + reload (Pay Later,
        // the original behavior). payNow=true → create, then advance straight
        // into the existing Make a Payment modal against the new charge.
        function submitExtension(payNow, btn, btnText, spinner) {
            const description = getExtDescription();
            const baseAmount  = parseFloat(document.getElementById('extBaseAmount').value);
            const person      = document.getElementById('extPerson').value;
            const addTax      = document.getElementById('extTaxAdd').checked;
            const notes       = document.getElementById('extNotes').value.trim();

            let valid = true;
            document.getElementById('extDescError').classList.toggle('hidden', !!description);
            if (!description) valid = false;
            document.getElementById('extAmountError').classList.toggle('hidden', !!(baseAmount && baseAmount > 0));
            if (!baseAmount || baseAmount <= 0) valid = false;
            document.getElementById('extPersonError').classList.toggle('hidden', !!person);
            if (!person) valid = false;
            if (!valid) return;

            // Both buttons lock while a create is in flight
            const payLaterBtn = document.getElementById('extSubmitBtn');
            const payNowBtn   = document.getElementById('extPayNowBtn');
            payLaterBtn.disabled = true;
            payNowBtn.disabled   = true;
            btnText.classList.add('hidden');
            spinner.classList.remove('hidden');

            fetch('{{ route("admin.order-management.orders.extension.store", $order->unique_id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    description:        description,
                    base_amount:        baseAmount,
                    add_tax:            addTax ? 1 : 0,
                    responsible_person: person,
                    notes:              notes || null,
                    request_uuid:       extRequestUuid,
                }),
            })
            .then(res => res.json().then(data => ({ status: res.status, data })))
            .then(({ status, data }) => {
                if (data.success) {
                    notyf.success(data.message);
                    closeExtensionModal();
                    if (payNow && data.billing_charge && window.BillingPayment) {
                        // Step 2: the SHARED payment modal, prefilled — pay
                        // now, or close as Pay Later (reloads to show the
                        // unpaid extension row).
                        window.BillingPayment.openForExtension(data.billing_charge, {
                            personId: person,
                            cards: window.beCustomerCards || [],
                        });
                    } else {
                        window.location.reload();
                    }
                } else if (status === 409 && data.duplicate) {
                    // Already created by an earlier click — just show it
                    notyf.success('Extension already created.');
                    window.location.reload();
                } else {
                    notyf.error(data.message || 'Failed to create extension charge.');
                }
            })
            .catch(err => {
                console.error('Extension charge error:', err);
                notyf.error('An error occurred. Please try again.');
            })
            .finally(() => {
                payLaterBtn.disabled = false;
                payNowBtn.disabled   = false;
                btnText.classList.remove('hidden');
                spinner.classList.add('hidden');
            });
        }

        document.getElementById('extSubmitBtn').addEventListener('click', function () {
            submitExtension(false, this, document.getElementById('extBtnText'), document.getElementById('extBtnSpinner'));
        });

        document.getElementById('extPayNowBtn').addEventListener('click', function () {
            submitExtension(true, this, document.getElementById('extPayNowText'), document.getElementById('extPayNowSpinner'));
        });

        updateExtSummary();
    })();

    </script>

    {{-- Operations History modal wiring (Enhancement 6/7). CSP-safe: delegated
         listeners only, no inline handlers; guarded so re-executed scripts
         (SPA navigation) never double-bind. --}}
    <script>
    (function () {
        if (window.__opsHistoryBound) { return; }
        window.__opsHistoryBound = true;

        function modal() { return document.getElementById('operationsHistoryModal'); }

        function openModal(url) {
            var m = modal();
            if (!m) { return; }
            m.classList.remove('hidden');
            var body = document.getElementById('opsHistoryBody');
            body.innerHTML = '<div class="py-10 text-center text-sm text-gray-400">Loading…</div>';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) {
                    if (!r.ok) { throw new Error('HTTP ' + r.status); }
                    return r.text();
                })
                .then(function (html) { body.innerHTML = html; })
                .catch(function (e) {
                    body.innerHTML = '<div class="py-10 text-center text-sm text-red-600">Could not load operations history (' + e.message + ').</div>';
                });
        }

        function closeModal() {
            var m = modal();
            if (m) { m.classList.add('hidden'); }
        }

        document.addEventListener('click', function (e) {
            var opener = e.target.closest('[data-ops-history-open]');
            if (opener) {
                e.preventDefault();
                openModal(opener.getAttribute('data-url'));
                return;
            }

            if (e.target.closest('[data-ops-history-close]')) {
                e.preventDefault();
                closeModal();
                return;
            }

            // Backdrop click (outside the dialog card) closes.
            var m = modal();
            if (m && !m.classList.contains('hidden') && e.target === m) {
                closeModal();
                return;
            }

            // Tab switching inside the fetched body.
            var tab = e.target.closest('[data-ops-tab]');
            if (tab) {
                var key = tab.getAttribute('data-ops-tab');
                var root = tab.closest('[data-ops-history-body]');
                if (!root) { return; }
                root.querySelectorAll('[data-ops-tab]').forEach(function (b) {
                    var active = b === tab;
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                    b.classList.toggle('border-sky-600', active);
                    b.classList.toggle('text-sky-700', active);
                    b.classList.toggle('border-transparent', !active);
                    b.classList.toggle('text-gray-500', !active);
                });
                root.querySelectorAll('[data-ops-panel]').forEach(function (p) {
                    p.classList.toggle('hidden', p.getAttribute('data-ops-panel') !== key);
                });
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeModal(); }
        });
    })();
    </script>

@endpush

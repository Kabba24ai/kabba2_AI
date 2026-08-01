{{--
    Canonical payment-STATUS filter options — ONE definition shared by every
    Order Management screen with a payment-status dropdown (Orders, Schedule).

    Default label is "Payment Status" (filters payment STATE, not method).
    Options come from OrderPaymentStatus::filterOptions() — canonical() plus
    Account, so on-account orders are findable — excluding the legacy Invoice*
    cases and the system-only Superseded. The Account label renders as
    "On Account" via the canonical presenter. Sorted A-Z by label; the default
    stays first.

    Values are enum values submitted as `payment_status`; every consumer's
    controller applies Order::scopeWherePaymentStatusFilter(), so the option
    vocabulary and the query semantics stay in sync.
--}}
<option value="">Payment Status</option>
@php
    $paymentStatusFilterOptions = collect(\App\Enums\Orders\OrderPaymentStatus::filterOptions())
        ->sortBy(fn ($status) => \App\Services\PaymentDescriptionPresenter::statusLabel($status))
        ->values();
@endphp
@foreach ($paymentStatusFilterOptions as $status)
    <option value="{{ $status->value }}" @selected(request('payment_status') === $status->value)>
        {{ \App\Services\PaymentDescriptionPresenter::statusLabel($status) }}
    </option>
@endforeach

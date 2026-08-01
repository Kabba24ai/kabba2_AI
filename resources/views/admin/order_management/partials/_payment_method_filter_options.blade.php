{{--
    Canonical payment-METHOD filter options — ONE definition shared by every
    Order Management screen with a payment-type dropdown (Orders, Schedule).

    Options come from OrderPaymentMethod::filterOptions() — canonical() + COD
    (excluded from canonical() as a completed method, but this filter still
    offers it so staff can find orders awaiting delivery payment) + Account.
    Sorted A-Z by label; only the "All Payment Types" default stays first.

    Values are enum values submitted as `payment_method`; every consumer's
    controller applies the identical whereHas('payments' / 'order.payments')
    predicate, so the option vocabulary and the query semantics stay in sync.
--}}
<option value="">All Payment Types</option>
@php
    $paymentMethodFilterOptions = collect(\App\Enums\Orders\OrderPaymentMethod::filterOptions())
        ->sortBy(fn ($method) => $method->label())
        ->values();
@endphp
@foreach ($paymentMethodFilterOptions as $method)
    <option value="{{ $method->value }}" @selected(request('payment_method') === $method->value)>
        {{ $method->label() }}
    </option>
@endforeach

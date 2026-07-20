@props([
    // BillingChargeStatus enum instance (billing_charges rows) OR a legacy
    // alert-status string ('pending'|'completed'|'resolved'|'uncollectible'
    // |null) — BillingChargePresenter maps both onto the one canonical
    // palette (Billing Charge Operations Commonization).
    'status' => null,
])

@php
    $badge = \App\Services\BillingChargePresenter::statusBadge($status);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ' . $badge['classes']]) }}>
    {{ $badge['label'] }}
</span>

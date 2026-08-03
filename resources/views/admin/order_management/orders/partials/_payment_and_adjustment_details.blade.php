@php
    // PAYMENTS AND PRE-TAX ADJUSTMENTS ARE DIFFERENT KINDS OF EVENT.
    //
    // Showing only payment rows made a $150 payment on a $223.50 order look
    // inexplicable. Goodwill and Store Credit did not pay the difference —
    // they reduced what was owed. They are listed separately, never as tender,
    // because presenting them as payments is the exact confusion this section
    // exists to remove.
    //
    // Read-only aggregation over records that already exist. No amount is
    // recomputed here and nothing is written.
    $paHistory     = \App\Services\Orders\OrderFinancialHistory::for($order);
    $paPayments    = $paHistory->paymentsReceived();
    $paAdjustments = $paHistory->pretaxAdjustments();

    // Basic financial facts — label, amount, status, date — are visible to
    // anyone who can see the order; they are on the customer's receipt.
    // Goodwill's INTERNAL authorization detail is management information and
    // needs the direct Spatie check: @can and the permission middleware both
    // route through the Gate, which AppServiceProvider makes non-refusing.
    $paCanSeeInternal = \App\Services\Goodwill\GoodwillPermissions::canApply(auth()->user());
@endphp

{{-- ── Payments Received ──────────────────────────────────────────────── --}}
<div>
    <div class="text-[11px] uppercase tracking-wide text-gray-400 mb-2">Payments Received</div>

    @forelse ($paPayments as $paPayment)
        <div class="bg-white border border-gray-200 rounded-lg p-3 text-sm mb-2">
            <div class="flex justify-between items-start">
                <div class="font-medium text-gray-900">{{ $paPayment->payment_method?->value ?? 'Payment' }}</div>
                <div class="font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($paPayment->amount) }}</div>
            </div>
            <div class="text-gray-500 text-xs mt-0.5">
                {{ \App\Helpers\CustomHelper::formatDateTime($paPayment->payment_datetime ?? $paPayment->created_at) }}
            </div>
            @if ($paPayment->createdBy?->full_name)
                <div class="text-gray-500 text-xs">Received by {{ $paPayment->createdBy->full_name }}</div>
            @endif
            @if ($paPayment->payment_note)
                <div class="text-gray-700 text-xs mt-1">Note: {{ $paPayment->payment_note }}</div>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500">No payment recorded for this order yet.</p>
    @endforelse
</div>

{{-- ── Pre-Tax Adjustments ────────────────────────────────────────────── --}}
@if (! empty($paAdjustments))
    <div>
        <div class="text-[11px] uppercase tracking-wide text-gray-400 mb-2">Pre-Tax Adjustments</div>

        @foreach ($paAdjustments as $paAdjustment)
            <div class="bg-white border border-gray-200 rounded-lg p-3 text-sm mb-2 {{ $paAdjustment['reversed'] ? 'opacity-70' : '' }}">
                <div class="flex justify-between items-start">
                    <div class="font-medium text-gray-900">{{ $paAdjustment['label'] }}</div>
                    <div class="font-semibold {{ $paAdjustment['reversed'] ? 'text-gray-500 line-through' : 'text-gray-900' }}">
                        {{ \App\Helpers\CustomHelper::formatCurrency($paAdjustment['amount']) }}
                    </div>
                </div>

                <div class="text-gray-500 text-xs mt-0.5">
                    {{ \App\Helpers\CustomHelper::formatDateTime($paAdjustment['at']) }}
                </div>

                <div class="mt-1">
                    {{-- A reversed adjustment stays visible and is marked. Hiding
                         it would conceal that a concession was granted and then
                         withdrawn. --}}
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                        {{ $paAdjustment['reversed'] ? 'bg-gray-200 text-gray-700' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $paAdjustment['status'] }}
                    </span>
                </div>

                @if ($paCanSeeInternal && $paAdjustment['has_internal_detail'])
                    <div class="mt-2 pt-2 border-t border-gray-100 space-y-0.5 text-xs text-gray-600">
                        @if ($paAdjustment['reason'])
                            <div class="flex justify-between"><span>Reason:</span><span class="text-gray-900">{{ $paAdjustment['reason'] }}</span></div>
                        @endif
                        @if ($paAdjustment['reason_category'])
                            <div class="flex justify-between"><span>Category:</span><span class="text-gray-900">{{ $paAdjustment['reason_category'] }}</span></div>
                        @endif
                        @if ($paAdjustment['approved_by'])
                            <div class="flex justify-between"><span>Approved by:</span><span class="text-gray-900">{{ $paAdjustment['approved_by'] }}</span></div>
                        @endif
                        @if ($paAdjustment['accepted_payment_total'] !== null)
                            <div class="flex justify-between">
                                <span>Accepted Payment Total:</span>
                                <span class="text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($paAdjustment['accepted_payment_total']) }}</span>
                            </div>
                        @endif
                        @if ($paAdjustment['rounding_residual'] !== null)
                            <div class="flex justify-between">
                                <span>Rounding Residual:</span>
                                <span class="text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($paAdjustment['rounding_residual']) }}</span>
                            </div>
                        @endif
                        @if ($paAdjustment['note'])
                            <div class="pt-1">Note: <span class="text-gray-900">{{ $paAdjustment['note'] }}</span></div>
                        @endif
                        @if ($paAdjustment['reversed'] && $paAdjustment['reversal_reason'])
                            <div class="pt-1">
                                Reversal reason: <span class="text-gray-900">{{ $paAdjustment['reversal_reason'] }}</span>
                                @if ($paAdjustment['reversed_by'])
                                    <span class="text-gray-500">({{ $paAdjustment['reversed_by'] }})</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif

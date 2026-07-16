{{--
    Phase 3D — Allocation Details (mission §2).

    One card per refund event on this order (not just incomplete ones —
    every refund, so a fully-completed multi-source refund's source
    breakdown remains visible after the fact, not just while it's stuck).
    Each source line reuses data already available via
    OrderPaymentRefundAllocation::originalPayment — nothing new is
    computed here beyond what PaymentAllocationService already exposes.

    Failed sources get a "Retry / Reallocate" action that seeds the SAME
    recovery-mode flow the Order Details "Resolve Refund" button already
    uses (enterRecoveryMode() in this page's JS) — no new endpoint, per
    the Phase 3D plan's explicit "surface, don't rebuild" scope for
    partial-failure recovery.

    Expects: $refundEvents — Collection<OrderPayment>, each with
    refundAllocations.originalPayment eager-loaded.
--}}
@if ($refundEvents->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-800">Refund Sources</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach ($refundEvents as $event)
                @php
                    $allocations = $event->refundAllocations;
                    $isRetryable = in_array($event->refund_operation_status, [
                        \App\Enums\Orders\RefundOperationStatus::PartiallyCompleted,
                        \App\Enums\Orders\RefundOperationStatus::Failed,
                    ], true);

                    $eventRetryPayload = null;
                    if ($isRetryable && $event->idempotency_token) {
                        $eventRetryPayload = [
                            'token' => $event->idempotency_token,
                            'amount' => (float) $event->refund_amount,
                            'payment_type' => $event->payment_method?->value === 'Card' ? 'CreditCard' : ($event->payment_method?->value ?? 'Cash'),
                            'calc_type' => $event->refund_calculation_type?->value ?? 'standard',
                            'sources' => $allocations->map(function ($a) {
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
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="text-xs text-gray-500">
                            Refund initiated {{ $event->refunded_at?->format('M j, Y g:ia') ?? $event->created_at?->format('M j, Y g:ia') }}
                            — requested {{ \App\Helpers\CustomHelper::formatCurrency((float) $event->refund_amount) }}
                        </div>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full
                            {{ match(true) {
                                $event->refund_operation_status === \App\Enums\Orders\RefundOperationStatus::Completed => 'bg-green-100 text-green-800',
                                $event->refund_operation_status === \App\Enums\Orders\RefundOperationStatus::PartiallyCompleted => 'bg-orange-100 text-orange-800',
                                $event->refund_operation_status === \App\Enums\Orders\RefundOperationStatus::Failed => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800',
                            } }}">
                            {{ $event->refund_operation_status?->value ? ucwords(str_replace('_', ' ', $event->refund_operation_status->value)) : 'Pending' }}
                        </span>
                    </div>

                    @if ($allocations->isEmpty())
                        <div class="mt-2 text-xs text-amber-600 font-medium">
                            Allocation unavailable for legacy transaction
                        </div>
                    @else
                        <div class="mt-2 space-y-1.5">
                            @foreach ($allocations->whereIn('status', [\App\Enums\Orders\OrderPaymentRefundAllocationStatus::Allocated, \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Pending]) as $a)
                                @php $original = $a->originalPayment; @endphp
                                <div class="flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-medium text-gray-800">
                                            {{ \App\Services\PaymentDescriptionPresenter::methodLabel($original?->payment_method) }}
                                            @if ($original?->payment_method === \App\Enums\Orders\OrderPaymentMethod::Card && $original?->card_number)
                                                &bull;&bull;&bull;&bull; {{ $original->card_number }}
                                            @endif
                                        </span>
                                        <span class="text-gray-400">
                                            — original payment {{ ($original?->payment_datetime ?? $original?->created_at)?->format('M j, Y') }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-gray-800">{{ \App\Helpers\CustomHelper::formatCurrency((float) $a->allocated_amount) }}</div>
                                        <div class="text-gray-400">Remaining refundable: {{ $original ? \App\Helpers\CustomHelper::formatCurrency(\App\Services\Orders\PaymentAllocationService::remainingRefundable($original)) : '—' }}</div>
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($allocations->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Failed) as $a)
                                @php $original = $a->originalPayment; @endphp
                                <div class="flex items-center justify-between text-xs bg-red-50 rounded px-2 py-1.5">
                                    <div>
                                        <span class="font-medium text-red-800">
                                            {{ \App\Services\PaymentDescriptionPresenter::methodLabel($original?->payment_method) }}
                                            @if ($original?->payment_method === \App\Enums\Orders\OrderPaymentMethod::Card && $original?->card_number)
                                                &bull;&bull;&bull;&bull; {{ $original->card_number }}
                                            @endif
                                            — Failed
                                        </span>
                                        @if ($a->failure_reason)
                                            <div class="text-red-600">{{ $a->failure_reason }}</div>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <div class="text-red-800">{{ \App\Helpers\CustomHelper::formatCurrency((float) $a->allocated_amount) }}</div>
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($allocations->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Superseded) as $a)
                                @php $original = $a->originalPayment; @endphp
                                <div class="flex items-center justify-between text-xs text-gray-400">
                                    <div>
                                        {{ \App\Services\PaymentDescriptionPresenter::methodLabel($original?->payment_method) }} — reallocated to a different source
                                    </div>
                                    <div>{{ \App\Helpers\CustomHelper::formatCurrency((float) $a->allocated_amount) }}</div>
                                </div>
                            @endforeach
                        </div>

                        @if ($eventRetryPayload)
                            <button type="button" class="allocation-detail-retry-btn mt-2 inline-flex items-center px-2.5 py-1 text-xs font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700"
                                data-retry='@json($eventRetryPayload)'>
                                Retry / Reallocate Failed Source
                            </button>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

{{--
    Additional Charges (Fuel & Damage) sourced from customer_accounts
    Variable: $additionalCharges — Collection<CustomerAccount>
--}}
@if($additionalCharges->isNotEmpty())
<div class="bg-white rounded-xl border border-amber-200 shadow-sm mb-4">
    <div class="flex items-center justify-between px-4 py-3 border-b border-amber-100 bg-amber-50 rounded-t-xl">
        <h2 class="font-semibold text-base text-amber-900 flex items-center gap-2">
            <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-amber-500" />
            Additional Charges
        </h2>
        <span class="text-xs text-amber-700 font-medium">{{ $additionalCharges->count() }} {{ Str::plural('charge', $additionalCharges->count()) }}</span>
    </div>

    <div class="divide-y divide-gray-100">
        @foreach($additionalCharges as $charge)
            @php
                $isFuel   = $charge->reason === 'Fuel Charge';
                $status   = $isFuel ? ($charge->fuel_alert_status ?? 'pending') : ($charge->damage_alert_status ?? 'pending');
                $statusBadge = match($status) {
                    'completed'     => 'bg-green-100 text-green-700',
                    'resolved'      => 'bg-blue-100 text-blue-700',
                    'uncollectible' => 'bg-gray-100 text-gray-500',
                    default         => 'bg-amber-100 text-amber-800',
                };
                $statusLabel = match($status) {
                    'completed'     => 'Paid',
                    'resolved'      => 'Resolved',
                    'uncollectible' => 'Uncollectible',
                    default         => 'Pending',
                };
                $taxAmount  = (float) ($charge->sales_tax ?? 0) > 0 && $charge->sales_tax_type === 'add'
                    ? round((float) $charge->amount * (float) $charge->sales_tax, 2)
                    : 0;
                $total = (float) $charge->amount + $taxAmount;
            @endphp

            <div class="flex items-center justify-between gap-4 px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    @if($isFuel)
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                            <svg fill="currentColor" class="w-4 h-4 text-orange-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                <path d="M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM160 144L160 240C160 248.8 167.2 256 176 256L304 256C312.8 256 320 248.8 320 240L320 144C320 135.2 312.8 128 304 128L176 128C167.2 128 160 135.2 160 144z"/>
                            </svg>
                        </span>
                    @else
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-red-600" />
                        </span>
                    @endif

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900">{{ $charge->reason }}</p>
                        <p class="text-xs text-gray-500 truncate">
                            Added {{ $charge->date?->format('M j, Y') ?? $charge->created_at->format('M j, Y') }}
                            @if($charge->responsibleUser)
                                &middot; {{ $charge->responsibleUser->full_name }}
                            @endif
                        </p>
                        @if($charge->notes)
                            <p class="text-xs text-gray-400 truncate mt-0.5">{{ $charge->notes }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <div class="text-right">
                        <p class="text-sm font-bold text-gray-900">${{ number_format($total, 2) }}</p>
                        @if($taxAmount > 0)
                            <p class="text-xs text-gray-400">+${{ number_format($taxAmount, 2) }} tax</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusBadge }}">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Running total --}}
    @php
        $pendingTotal = $additionalCharges
            ->filter(fn($c) => ($c->fuel_alert_status ?? $c->damage_alert_status) === 'pending')
            ->sum(fn($c) => (float) $c->amount);
    @endphp
    @if($pendingTotal > 0)
        <div class="px-4 py-2.5 bg-amber-50 border-t border-amber-100 rounded-b-xl flex justify-end items-center gap-2">
            <span class="text-xs text-amber-700 font-medium">Outstanding:</span>
            <span class="text-sm font-bold text-amber-900">${{ number_format($pendingTotal, 2) }}</span>
        </div>
    @endif
</div>
@endif

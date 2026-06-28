{{--
    Billing Engine — consolidated charge display for Order Details.
    Variable: $billingCharges — Collection<BillingCharge>
    Source: billing_charges WHERE parent_order_id = order.id
--}}
@if($billingCharges->isNotEmpty())
<div class="bg-white rounded-xl border border-green-200 shadow-sm mb-4">
    {{-- Header --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-green-100 bg-green-50 rounded-t-xl">
        <h2 class="font-semibold text-base text-green-900 flex items-center gap-2">
            <x-heroicon-o-bolt class="w-4 h-4 text-green-600" />
            Billing Engine
        </h2>
        <span class="text-xs text-green-700 font-medium">
            {{ $billingCharges->count() }} {{ Str::plural('charge', $billingCharges->count()) }}
        </span>
    </div>

    {{-- Charge rows --}}
    <div class="divide-y divide-gray-100">
        @foreach($billingCharges as $charge)
            @php
                $typeIcons = [
                    'fuel'      => ['icon' => 'heroicon-o-fire',                'bg' => 'bg-orange-100', 'color' => 'text-orange-600'],
                    'damage'    => ['icon' => 'heroicon-o-exclamation-triangle', 'bg' => 'bg-red-100',    'color' => 'text-red-600'],
                    'extension' => ['icon' => 'heroicon-o-calendar',            'bg' => 'bg-blue-100',   'color' => 'text-blue-600'],
                ];
                // billing_charge_type is cast to BillingChargeType enum — use ->value for key, ->label() for display
                $typeEnum = $charge->billing_charge_type;
                $typeKey  = $typeEnum?->value ?? 'misc';
                $typeLabel = $typeEnum?->label() ?? 'Charge';
                $iconDef  = $typeIcons[$typeKey] ?? ['icon' => 'heroicon-o-currency-dollar', 'bg' => 'bg-gray-100', 'color' => 'text-gray-500'];

                $status      = strtolower($charge->status ?? 'pending');
                $statusBadge = match($status) {
                    'paid'     => 'bg-green-100 text-green-700',
                    'resolved' => 'bg-blue-100 text-blue-700',
                    default    => 'bg-amber-100 text-amber-800',
                };
                $statusLabel = match($status) {
                    'paid'     => 'Paid',
                    'resolved' => 'Resolved',
                    default    => 'Pending',
                };

                $amount    = (float) ($charge->amount ?? 0);
                $taxAmount = (float) ($charge->tax_amount ?? 0);
                $total     = $amount + $taxAmount;
            @endphp

            <div class="flex items-center justify-between gap-4 px-4 py-3">
                {{-- Left: icon + details --}}
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full {{ $iconDef['bg'] }} flex items-center justify-center">
                        <x-dynamic-component :component="$iconDef['icon']" class="w-4 h-4 {{ $iconDef['color'] }}" />
                    </span>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900">{{ $typeLabel }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $charge->created_at?->format('M j, Y') }}
                            @if($charge->createdBy)
                                &middot; {{ $charge->createdBy->full_name }}
                            @endif
                            @if($charge->source_module)
                                &middot; <span class="italic">{{ $charge->source_module }}</span>
                            @endif
                        </p>
                        @if($charge->notes)
                            <p class="text-xs text-gray-400 truncate mt-0.5">{{ $charge->notes }}</p>
                        @endif
                        @if($charge->childOrder)
                            <a href="{{ route('admin.order-management.orders.edit', $charge->childOrder->unique_id) }}"
                               class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline mt-0.5">
                                <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3" />
                                Child Order #{{ $charge->childOrder->order_number }}
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Right: amount + status --}}
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

    {{-- Outstanding total footer --}}
    @php
        $outstanding = $billingCharges
            ->filter(fn($c) => strtolower($c->status ?? 'pending') === 'pending')
            ->sum(fn($c) => (float) $c->amount + (float) $c->tax_amount);
    @endphp
    @if($outstanding > 0)
        <div class="px-4 py-2.5 bg-green-50 border-t border-green-100 rounded-b-xl flex justify-end items-center gap-2">
            <span class="text-xs text-green-700 font-medium">Outstanding:</span>
            <span class="text-sm font-bold text-green-900">${{ number_format($outstanding, 2) }}</span>
        </div>
    @endif
</div>
@endif

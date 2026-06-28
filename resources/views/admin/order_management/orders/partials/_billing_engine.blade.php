{{--
    Billing Engine — consolidated charge display for Order Details.
    Variable: $billingCharges — Collection<BillingCharge>
    Source: billing_charges WHERE parent_order_id = order.id

    Both billing_charge_type and status are cast to backed enums on the model.
    All enum usage goes through ->value, ->label(), ->badgeClass(), ->isOpen().

    Action icons appear on fuel_charge rows only (not damage, extension, or other types).
    Payment modal is a standard form POST to admin.dashboard.paymentstore (source=crm).
    Resolve / Uncollectible / Note / Adjust use AJAX via new billing-engine-specific routes.
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

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider w-44">Charge</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Details</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Added By</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Date</th>
                    <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Amount</th>
                    <th class="text-center px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Status</th>
                    <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Outstanding</th>
                    <th class="text-center px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($billingCharges as $charge)
                    @php
                        // 'fuel' uses the gas pump SVG (same as Additional Charges and Dashboard alerts)
                        $typeIconClasses = [
                            'damage'    => ['heroicon' => 'heroicon-o-exclamation-triangle', 'bg' => 'bg-red-100',  'color' => 'text-red-600'],
                            'extension' => ['heroicon' => 'heroicon-o-calendar',             'bg' => 'bg-blue-100', 'color' => 'text-blue-600'],
                        ];

                        $typeEnum   = $charge->billing_charge_type;
                        $statusEnum = $charge->status;

                        $typeLabel   = $typeEnum?->label()           ?? 'Charge';
                        $typeValue   = $typeEnum?->value              ?? '';
                        $isFuelIcon  = $typeValue === 'fuel';
                        $iconDef     = $typeIconClasses[$typeValue]   ?? ['heroicon' => 'heroicon-o-currency-dollar', 'bg' => 'bg-gray-100', 'color' => 'text-gray-500'];

                        $statusLabel = $statusEnum?->label()      ?? 'Pending';
                        $statusBadge = $statusEnum?->badgeClass()  ?? 'bg-amber-100 text-amber-800';
                        $isOpen      = $statusEnum?->isOpen()      ?? false;

                        $base        = $charge->amount    ?? 0.0;
                        $tax         = $charge->tax_amount ?? 0.0;
                        $total       = $base + $tax;

                        $isFuel      = $typeValue === 'fuel';
                        $addedBy     = $charge->createdBy?->full_name ?? $charge->responsiblePerson?->full_name ?? '—';

                        // Payment needs the legacy CA unique_id
                        $caUniqueId  = $charge->legacyCustomerAccount?->unique_id ?? '';
                    @endphp

                    <tr class="hover:bg-gray-50 transition-colors">
                        {{-- Charge --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                @if($isFuelIcon)
                                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-orange-100 flex items-center justify-center">
                                        <svg fill="currentColor" class="w-3.5 h-3.5 text-orange-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                            <path d="M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM160 144L160 240C160 248.8 167.2 256 176 256L304 256C312.8 256 320 248.8 320 240L320 144C320 135.2 312.8 128 304 128L176 128C167.2 128 160 135.2 160 144z"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="flex-shrink-0 w-7 h-7 rounded-full {{ $iconDef['bg'] }} flex items-center justify-center">
                                        <x-dynamic-component :component="$iconDef['heroicon']" class="w-3.5 h-3.5 {{ $iconDef['color'] }}" />
                                    </span>
                                @endif
                                <span class="font-semibold text-gray-900 text-xs">{{ $typeLabel }}</span>
                            </div>
                        </td>

                        {{-- Details --}}
                        <td class="px-4 py-3 max-w-xs">
                            @if($charge->notes)
                                <span class="text-xs text-gray-600 block truncate" title="{{ $charge->notes }}">{{ $charge->notes }}</span>
                            @endif
                            @if($charge->childOrder)
                                <a href="{{ route('admin.order-management.orders.edit', $charge->childOrder->unique_id) }}"
                                   class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline mt-0.5">
                                    <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3" />
                                    Child #{{ $charge->childOrder->order_number }}
                                </a>
                            @endif
                        </td>

                        {{-- Added By --}}
                        <td class="px-4 py-3">
                            <span class="text-xs text-gray-700">{{ $addedBy }}</span>
                        </td>

                        {{-- Date --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-xs text-gray-700">{{ $charge->created_at?->format('M j, Y') }}</span>
                        </td>

                        {{-- Amount Breakdown --}}
                        <td class="px-4 py-3 text-right">
                            <div class="text-xs text-gray-500">Base: ${{ number_format($base, 2) }}</div>
                            <div class="text-xs text-gray-400">Tax: ${{ number_format($tax, 2) }}</div>
                            <div class="text-sm font-bold text-gray-900">${{ number_format($total, 2) }}</div>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusBadge }}">
                                {{ $statusLabel }}
                            </span>
                        </td>

                        {{-- Outstanding --}}
                        <td class="px-4 py-3 text-right">
                            @if($isOpen)
                                <span class="text-sm font-semibold text-red-600">${{ number_format($total, 2) }}</span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Actions (fuel rows only) --}}
                        <td class="px-4 py-3 text-center">
                            @if($isFuel)
                                <div class="flex items-center justify-center gap-1.5"
                                     data-be-unique-id="{{ $charge->unique_id }}"
                                     data-be-ca-unique="{{ $caUniqueId }}"
                                     data-be-customer-id="{{ $charge->customer_id }}"
                                     data-be-total="{{ $total }}"
                                     data-be-is-open="{{ $isOpen ? '1' : '0' }}">

                                    @if($isOpen)
                                        {{-- Make a Payment --}}
                                        <button type="button"
                                            onclick="beOpenPayment(this.closest('[data-be-unique-id]'))"
                                            title="Make a Payment"
                                            class="w-6 h-6 rounded flex items-center justify-center text-green-600 hover:bg-green-100 transition"
                                            aria-label="Make a Payment">
                                            <x-heroicon-o-currency-dollar class="w-4 h-4" />
                                        </button>

                                        {{-- Mark as Resolved --}}
                                        <button type="button"
                                            onclick="beOpenResolve(this.closest('[data-be-unique-id]'))"
                                            title="Mark as Resolved"
                                            class="w-6 h-6 rounded flex items-center justify-center text-blue-600 hover:bg-blue-100 transition"
                                            aria-label="Mark as Resolved">
                                            <x-heroicon-o-check-circle class="w-4 h-4" />
                                        </button>

                                        {{-- Mark as Uncollectible --}}
                                        <button type="button"
                                            onclick="beOpenUncollectible(this.closest('[data-be-unique-id]'))"
                                            title="Mark as Uncollectible"
                                            class="w-6 h-6 rounded flex items-center justify-center text-red-500 hover:bg-red-100 transition"
                                            aria-label="Mark as Uncollectible">
                                            <x-heroicon-o-x-circle class="w-4 h-4" />
                                        </button>

                                        {{-- Adjust Fuel Charge --}}
                                        <button type="button"
                                            onclick="beOpenAdjust(this.closest('[data-be-unique-id]'))"
                                            title="Adjust Fuel Charge"
                                            class="w-6 h-6 rounded flex items-center justify-center text-amber-500 hover:bg-amber-100 transition"
                                            aria-label="Adjust Fuel Charge">
                                            <x-heroicon-o-adjustments-horizontal class="w-4 h-4" />
                                        </button>
                                    @endif

                                    {{-- Add Note (always available for fuel rows) --}}
                                    <button type="button"
                                        onclick="beOpenNote(this.closest('[data-be-unique-id]'))"
                                        title="Add Note"
                                        class="w-6 h-6 rounded flex items-center justify-center text-gray-500 hover:bg-gray-100 transition"
                                        aria-label="Add Note">
                                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Outstanding total footer --}}
    @php
        $outstanding = $billingCharges
            ->filter(fn($c) => $c->status?->isOpen())
            ->sum(fn($c) => ($c->amount ?? 0.0) + ($c->tax_amount ?? 0.0));
    @endphp
    @if($outstanding > 0)
        <div class="px-4 py-2.5 bg-green-50 border-t border-green-100 rounded-b-xl flex justify-end items-center gap-2">
            <span class="text-xs text-green-700 font-medium">Outstanding:</span>
            <span class="text-sm font-bold text-green-900">${{ number_format($outstanding, 2) }}</span>
        </div>
    @endif
</div>
@endif

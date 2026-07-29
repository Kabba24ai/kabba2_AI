{{--
    Billing Engine — consolidated charge display for Order Details.
    Variable: $billingCharges — Collection<BillingCharge>
    Source: billing_charges WHERE parent_order_id = order.id

    Billing Charge Operations Commonization: rows render through the SHARED
    billing components — x-admin.billing.charge-status-badge,
    charge-origin-badge, and charge-actions — the same vocabulary, order,
    icons, and tooltips as the Fuel Charge Workspace. Behavior comes from
    the shared admin/charges/_action_modals bundle (rows are action-mode
    'charge': billing-charges.* endpoints, unchanged), payment from the
    shared admin/billing/_payment_modal (window.BillingPayment), and Refund
    posts to the pre-existing linked-refund endpoint — shown only on PAID
    fuel/damage charges with a remaining refundable balance.
    Page-specific actions (View Damage Details, extension Delete) are
    dispatched through ChargeActions.onAction to handlers in edit.blade.php.
--}}
@php
    $beCustomerCards = $order->customer?->cards
        ?->map(fn ($c) => ['id' => $c->unique_id, 'label' => $c->card_number])
        ->values() ?? collect();
    $beCustomerName = $order->customer?->full_name ?? $order->customer_name;

    // Add-to-Account eligibility is an order-level property (all charges share
    // the order's customer) — compute once. The service re-checks server-side.
    $beAcctEligible = \App\Services\Billing\AddChargeToAccountService::customerIsEligible($order->customer);
    $beAcctBalance  = (float) ($order->customer?->available_credit_balance ?? 0);
@endphp
<div class="bg-white rounded-xl border border-green-200 shadow-sm mb-4">
    {{-- Header --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-green-100 bg-green-50 rounded-t-xl">
        <h2 class="font-semibold text-base text-green-900 flex items-center gap-2">
            <x-heroicon-o-bolt class="w-4 h-4 text-green-600" />
            Billing Engine
        </h2>
        <div class="flex items-center gap-2">
            {{-- Fuel Charge --}}
            <button type="button" id="beFuelBtn" title="Add Fuel Charge Alert"
                onclick="window.NewFuelCharge.open({
                    lockOrderContext: true,
                    orderId: {{ (int) $order->id }},
                    orderLabel: @js('Order ' . $order->order_number),
                    customerId: {{ (int) ($order->customer_id ?? 0) }},
                    customerName: @js($order->customer?->full_name ?? $order->customer_name),
                })"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold transition-colors">
                <svg fill="currentColor" class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                    <path d="M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM160 144L160 240C160 248.8 167.2 256 176 256L304 256C312.8 256 320 248.8 320 240L320 144C320 135.2 312.8 128 304 128L176 128C167.2 128 160 135.2 160 144z"/>
                </svg>
                Fuel Charge
            </button>
            {{-- Damage Charge --}}
            <button type="button" id="beDamageBtn" title="Add Damage Alert"
                onclick="document.getElementById('orderDamageAlertModal').classList.remove('hidden');document.getElementById('orderDamageAlertModal').classList.add('flex')"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-red-500 hover:bg-red-600 text-white text-xs font-semibold transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" class="w-3.5 h-3.5">
                    <path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/>
                </svg>
                Damage Charge
            </button>
            {{-- Order Enhancement --}}
            <button type="button" id="beExtensionBtn" title="Add Order Enhancement" onclick="openExtensionModal()"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors">
                <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                Order Enhancement
            </button>
            @if($billingCharges->isNotEmpty())
            <span class="text-xs text-green-700 font-medium pl-1 border-l border-green-200">
                {{ $billingCharges->count() }} {{ Str::plural('charge', $billingCharges->count()) }}
            </span>
            @endif
        </div>
    </div>

    @if($billingCharges->isNotEmpty())
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
                    <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($billingCharges as $charge)
                    @php
                        // 'fuel' uses the gas pump SVG (same as Additional Charges and Dashboard alerts)
                        $typeIconClasses = [
                            'damage'        => ['heroicon' => 'heroicon-o-exclamation-triangle',   'bg' => 'bg-red-100',    'color' => 'text-red-600'],
                            'extension'     => ['heroicon' => 'heroicon-o-calendar',               'bg' => 'bg-blue-100',   'color' => 'text-blue-600'],
                            'service_ticket'=> ['heroicon' => 'heroicon-o-wrench-screwdriver',      'bg' => 'bg-purple-100', 'color' => 'text-purple-600'],
                        ];

                        $typeEnum   = $charge->billing_charge_type;
                        $statusEnum = $charge->status;

                        $typeLabel   = $typeEnum?->label()           ?? 'Charge';
                        $typeValue   = $typeEnum?->value              ?? '';
                        $isFuelIcon  = $typeValue === 'fuel';
                        $iconDef     = $typeIconClasses[$typeValue]   ?? ['heroicon' => 'heroicon-o-currency-dollar', 'bg' => 'bg-gray-100', 'color' => 'text-gray-500'];

                        $isOpen      = $statusEnum?->isOpen()      ?? false;
                        $isPaid      = $statusEnum === \App\Enums\Billing\BillingChargeStatus::Paid;
                        $origin      = \App\Services\BillingChargePresenter::originForCharge($charge);

                        $base        = $charge->amount    ?? 0.0;
                        $tax         = $charge->tax_amount ?? 0.0;
                        $total       = $base + $tax;
                        // Sales Tax Architecture Correction: surface WHICH
                        // treatment produced this base/tax split, not just
                        // the resulting dollar amounts.
                        $taxTreatmentLabel = match ($charge->tax_type) {
                            'add'     => 'Add Sales Tax',
                            'reverse' => 'Reverse Sales Tax',
                            default   => 'Tax Free',
                        };

                        $isFuel          = $typeValue === 'fuel';
                        $isDamage        = $typeValue === 'damage';
                        $isExtension     = $typeValue === 'extension';
                        $isService       = $typeValue === 'service_ticket';
                        $addedBy         = $charge->createdBy?->full_name ?? $charge->responsiblePerson?->full_name ?? '—';

                        // Payment needs the legacy CA unique_id (null for extension charges)
                        $caUniqueId      = $charge->legacyCustomerAccount?->unique_id ?? '';

                        // Coordinated deletion context (extension rows only):
                        // the child order number for the confirmation text and
                        // the payment state driving the disposition requirement
                        $extChildNumber  = $isExtension ? ($charge->childOrder?->order_number ?? '') : '';
                        $extPayState     = $isExtension
                            ? \App\Services\ExtensionTransactionService::paymentState($charge, $charge->childOrder)
                            : '';
                        // View Damage Details requires a linked OrderProduct (only mobile-checklist damage charges set this)
                        $hasOrderProduct = $isDamage && $charge->orderProduct !== null;

                        // Linked-refund availability — PAID fuel/damage with a
                        // remaining refundable balance (allocation-aware; the
                        // endpoint re-derives all of this server-side).
                        $refundRemaining = ($isPaid && ($isFuel || $isDamage))
                            ? (float) \App\Services\Orders\BillingChargeRefundService::remainingRefundable($charge)['total']
                            : 0.0;

                        // Add to Account — supported charge types, still open,
                        // with an eligible active credit account. The service
                        // re-validates all of this under a row lock.
                        $canAddToAccount = $isOpen && $beAcctEligible
                            && \App\Services\Billing\AddChargeToAccountService::typeIsSupported($charge);
                        // Whether the receivable is already booked (drives the
                        // "balance after" the confirmation modal shows).
                        $alreadyInAr = $charge->customer_account_id !== null;

                        // Capability from charge state + business rules only
                        // (approved architecture rule) — never from the page.
                        $rowActions = [];
                        if ($isFuel || $isDamage) {
                            if ($isOpen) {
                                $rowActions = ['history', 'notes', 'adjust', 'payment', 'resolve', 'uncollectible'];
                                if ($canAddToAccount) {
                                    $rowActions[] = 'add-to-account';
                                }
                            } else {
                                $rowActions = ['history', 'notes'];
                                if ($refundRemaining > 0) {
                                    $rowActions[] = 'refund';
                                }
                            }
                            if ($hasOrderProduct) {
                                $rowActions[] = 'view-damage';
                            }
                        } elseif ($isExtension) {
                            $rowActions = $isOpen
                                ? ['notes', 'adjust', 'payment', 'delete']
                                : ['notes', 'delete'];
                            if ($isOpen && $canAddToAccount) {
                                $rowActions[] = 'add-to-account';
                            }
                        } elseif ($isService) {
                            // Service settlement charge (ST-2b): payable + noteable
                            // through the shared surfaces, exactly like other
                            // charges. NOT adjustable (the settlement is the
                            // source of truth) and NOT refundable via the
                            // fuel/damage linked-refund path (unsupported type).
                            $rowActions = $isOpen ? ['payment', 'notes'] : ['notes'];
                        }
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
                                @if ($origin)
                                    <x-admin.billing.charge-origin-badge
                                        :origin="$charge->order_product_id !== null ? 'checklist' : 'manual'"
                                        :title="$origin['title']" />
                                @endif
                            </div>
                        </td>

                        {{-- Details --}}
                        <td class="px-4 py-3 max-w-xs">
                            {{-- The chosen Order Enhancement description (Rental Extension,
                                 Delivery / Pickup Added, …) — reflects what was selected,
                                 not a blanket type name. Sourced from the child order note. --}}
                            @if($isExtension && $charge->childOrder?->order_note)
                                <span class="text-xs font-medium text-gray-800 block truncate" title="{{ $charge->childOrder->order_note }}">{{ $charge->childOrder->order_note }}</span>
                            @endif
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
                            <div class="text-xs text-gray-400" title="Sales Tax Treatment">{{ $taxTreatmentLabel }}</div>
                            <div class="text-xs text-gray-500">Base: ${{ number_format($base, 2) }}</div>
                            <div class="text-xs text-gray-400">Tax: ${{ number_format($tax, 2) }}</div>
                            <div class="text-sm font-bold text-gray-900">${{ number_format($total, 2) }}</div>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3 text-center">
                            <x-admin.billing.charge-status-badge :status="$statusEnum ?? 'pending'" />
                        </td>

                        {{-- Outstanding --}}
                        <td class="px-4 py-3 text-right">
                            @if($isOpen)
                                <span class="text-sm font-semibold text-red-600">${{ number_format($total, 2) }}</span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Actions — the canonical shared action bar (right-aligned) --}}
                        <td class="px-4 py-3 text-right">
                            @if(!empty($rowActions))
                                <div class="flex items-center justify-end"
                                     data-charge-row
                                     data-action-mode="charge"
                                     data-type="{{ $typeValue }}"
                                     data-bc-id="{{ $charge->unique_id }}"
                                     data-ca-id="{{ $caUniqueId }}"
                                     data-customer-id="{{ $charge->customer_id }}"
                                     data-customer-name="{{ $beCustomerName }}"
                                     data-order-db-id="{{ $order->id }}"
                                     data-order-uid="{{ $order->unique_id }}"
                                     data-order-number="{{ $order->order_number }}"
                                     data-amount="{{ number_format((float) $base, 2, '.', '') }}"
                                     data-amount-total="{{ number_format((float) $total, 2, '.', '') }}"
                                     data-cards='@json($beCustomerCards)'
                                     data-refund-remaining="{{ number_format($refundRemaining, 2, '.', '') }}"
                                     data-child-number="{{ $extChildNumber }}"
                                     data-paystate="{{ $extPayState }}"
                                     data-charge-label="{{ $typeLabel }}"
                                     data-account-balance="{{ number_format($beAcctBalance, 2, '.', '') }}"
                                     data-already-in-ar="{{ $alreadyInAr ? '1' : '0' }}">
                                    <x-admin.billing.charge-actions :actions="$rowActions" />
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
    @else
    <div class="px-4 py-6 text-center text-sm text-gray-400">No billing charges yet.</div>
    @endif
</div>

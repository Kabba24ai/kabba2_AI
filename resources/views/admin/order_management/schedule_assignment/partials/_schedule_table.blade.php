<div id="schedule-loading" class="hidden"></div>
<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm ">
    <thead class="bg-gray-100 text-gray-600 sticky top-0 z-10">
        <tr>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Product</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Order</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Customer</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Delivery Address</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Phone</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equipment</th>
            <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">Equipment ID</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Location</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Delivery Date</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Return Date</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Payment</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 text-gray-900 whitespace-nowrap">
        @forelse ($orderProducts as $orderProduct)
            <tr id="order-row-{{ $orderProduct->id }}" class="hover:bg-gray-50">
                <td class="whitespace-nowrap px-4 py-3 text-left min-w-4xs max-w-4xs">
                    {{ $orderProduct->product_name }}

                    @php
                        $categories = $orderProduct->product?->categories ?? collect();
                        $count = $categories->count();
                    @endphp

                    @if ($count === 1)
                        <div class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <span>{{ $categories->first()->title }}</span>
                        </div>
                    @elseif ($count > 1)
                        @php
                            $tooltipHtml = '<div class="font-semibold mb-2">Categories:</div>';

                            foreach ($categories as $cat) {
                                $tooltipHtml .=
                                    '<div class="flex gap-2"><span>•</span><span>' . e($cat->title) . '</span></div>';
                            }
                        @endphp
                        <span class="tooltip-trigger block text-blue-600 cursor-pointer text-xs"
                            data-tooltip-html="{{ $tooltipHtml }}">
                            Categories ({{ $count }})
                        </span>
                    @endif
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-center">
                    {!! $orderProduct->order->view_link !!}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-left">

                    <div class="font-medium">
                        {{ $orderProduct->order->customer_name }}
                    </div>

                    @php
                        $customer = $orderProduct->order->customer;
                    @endphp

                    @if ($customer && $customer->company_name)
                        <div class="text-xs text-gray-500 mt-1 ">

                            @if (!empty($customer->company_website))
                                <!-- With website: underline + clickable -->
                                <a href="{{ $customer->company_website }}" target="_blank" class="underline ">
                                    {{ $customer->company_name }}
                                </a>
                            @else
                                <!-- No website: same style but not clickable -->
                                <span class="">
                                    {{ $customer->company_name }}
                                </span>
                            @endif

                        </div>
                    @endif

                </td>

                <td class="whitespace-nowrap px-4 py-3 truncate min-w-xs max-w-xs">
                    {{ $orderProduct->order->shippingAddress->full_address }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-left ">{{ $orderProduct->order->shippingAddress->phone }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center">
                    @if ($orderProduct?->checklistQuestions->isNotEmpty())
                        <button type="button" class="text-blue-600 underline equipment-assign-btn"
                            data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                            data-order-product-name="{{ $orderProduct->product_name }}"
                            data-order="{{ $orderProduct?->order?->order_number }}">
                            {{ $orderProduct->equipment_details['equipment_name'] ?? 'Assign' }}
                        </button>
                    @else
                        <button type="button" class="text-blue-600 underline equipment-assign-btn"
                            data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                            data-order-product-name="{{ $orderProduct->product_name }}"
                            data-order="{{ $orderProduct?->order?->order_number }}">
                            {{ $orderProduct->softAssignment?->equipment->equipment_name ?: 'Assign' }}
                        </button>
                    @endif
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center ">
                    @if ($orderProduct?->checklistQuestions->isNotEmpty())
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                            {{ $orderProduct->equipment_details['equipment_id'] }}
                        </span>
                    @else
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                            {{ $orderProduct->softAssignment?->equipment->equipment_id ?? '-' }}
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3 text-center">
                    <div class="inline-flex items-center gap-1">
                        @if ($orderProduct?->checklistQuestions->isNotEmpty())
                            <a href="{{ route('admin.crm.customers.view', $orderProduct?->order?->customer->unique_id) }}"
                                target="_blank" class="text-blue-600 hover:underline">
                                {{ $orderProduct?->order->customer_name ?? '-' }}
                            </a>
                        @else
                            @if ($orderProduct?->softAssignment?->equipment?->store?->store_name)
                                {{ $orderProduct?->softAssignment?->equipment?->store->store_name }}
                            @else
                                -
                            @endif
                        @endif
                    </div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center">
                    @php
                        $iconColor =
                            $orderProduct->delivery_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                    @endphp
                    <div class="flex flex-col items-center">
                        <div class="flex items-center justify-center gap-1">
                            @if (!empty($orderProduct->delivery_transport_mode))
                                @if ($orderProduct->delivery_transport_mode === 'Truck')
                                    <x-heroicon-o-truck class="w-4 h-4 {{ $iconColor }}" />
                                @else
                                    <x-heroicon-o-building-storefront class="w-4 h-4 {{ $iconColor }}" />
                                @endif
                            @endif
                            <span>
                                {{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date, 'M d, y') : 'N/A' }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-500 mt-1">
                            {{ $orderProduct->delivery_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->delivery_time) : ' ' }}
                        </span>
                    </div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center">
                    @php
                        $iconColor =
                            $orderProduct->pickup_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                    @endphp
                    <div class="flex flex-col items-center">
                        <div class="flex items-center justify-center gap-1">
                            @if (!empty($orderProduct->pickup_transport_mode))
                                @if ($orderProduct->pickup_transport_mode === 'Truck')
                                    <x-heroicon-o-truck class="w-4 h-4 {{ $iconColor }}" />
                                @else
                                    <x-heroicon-o-building-storefront class="w-4 h-4 {{ $iconColor }}" />
                                @endif
                            @endif
                            <span>
                                {{ $orderProduct->pickup_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->pickup_date, 'M d, y') : 'N/A' }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-500 mt-1">
                            {{ $orderProduct->pickup_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->pickup_time) : ' ' }}
                        </span>
                    </div>
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-center">
                    {!! \App\Helpers\CustomHelper::statusBadge($orderProduct->order->last_payment_status) !!}
                </td>
                <td class="whitespace-nowrap px-4 py-3">
                    <div class="flex gap-2 items-center justify-center">
                        <a href="{{ route('admin.order-management.orders.edit', $orderProduct->order->unique_id) }}"
                            class="text-sky-600 hover:text-sky-800" title="View" target="_blank">
                            <x-heroicon-o-eye class="w-4 h-4" />
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="12" class="text-center text-sm text-gray-500 px-4 py-6">
                    No order found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@push('css')
    <style>
        .tw-tooltip::before {
            content: "";
            position: absolute;
            top: -6px;
            left: 16px;
            width: 10px;
            height: 10px;
            background: #fff;
            border-left: 1px solid rgb(229 231 235);
            /* gray-200 */
            border-top: 1px solid rgb(229 231 235);
            transform: rotate(45deg);
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.__scheduleTooltipInitialized) return;
            window.__scheduleTooltipInitialized = true;

            const tooltip = document.createElement('div');
            tooltip.className =
                'fixed hidden rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700 shadow-lg max-w-xs z-[9999]';
            document.body.appendChild(tooltip);

            let activeTrigger = null;
            let timeouts = {
                open: null,
                close: null
            };

            const DELAYS = {
                open: 120,
                close: 80
            };
            const GAP = 8;

            function hide() {
                clearTimeout(timeouts.open);
                clearTimeout(timeouts.close);
                activeTrigger = null;
                tooltip.classList.add('hidden');
            }

            function position(trigger) {
                if (!trigger) return;

                tooltip.classList.remove('hidden');
                const triggerRect = trigger.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();

                let top = triggerRect.bottom + GAP;
                let left = triggerRect.left;

                if (left + tooltipRect.width > window.innerWidth - 10) {
                    left = window.innerWidth - tooltipRect.width - 10;
                }
                if (left < 10) left = 10;

                if (top + tooltipRect.height > window.innerHeight - 10) {
                    top = triggerRect.top - GAP - tooltipRect.height;
                }

                tooltip.style.cssText = `top: ${top}px; left: ${left}px;`;
            }

            function show(trigger) {
                clearTimeout(timeouts.close);
                timeouts.open = setTimeout(() => {
                    activeTrigger = trigger;
                    tooltip.innerHTML = trigger.dataset.tooltipHtml || '';
                    requestAnimationFrame(() => position(trigger));
                }, DELAYS.open);
            }

            function scheduleHide(trigger) {
                clearTimeout(timeouts.open);
                timeouts.close = setTimeout(() => {
                    if (activeTrigger === trigger) hide();
                }, DELAYS.close);
            }

            function getTrigger(target) {
                return target?.closest?.('.tooltip-trigger');
            }

            document.addEventListener('pointerover', event => {
                const trigger = getTrigger(event.target);
                if (!trigger || trigger.contains(event.relatedTarget)) return;
                show(trigger);
            });

            document.addEventListener('pointerout', event => {
                const trigger = getTrigger(event.target);
                if (!trigger || trigger.contains(event.relatedTarget)) return;
                scheduleHide(trigger);
            });

            document.addEventListener('focusin', event => {
                const trigger = getTrigger(event.target);
                if (trigger) show(trigger);
            });

            document.addEventListener('focusout', event => {
                const trigger = getTrigger(event.target);
                if (trigger) scheduleHide(trigger);
            });

            ['scroll', 'resize'].forEach(event => {
                window.addEventListener(event, () => {
                    if (activeTrigger && !tooltip.classList.contains('hidden')) {
                        requestAnimationFrame(() => position(activeTrigger));
                    }
                }, {
                    passive: true
                });
            });
        });
    </script>
@endpush

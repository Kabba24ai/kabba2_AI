<div class="grid grid-cols-2 gap-4">

    {{-- ===== DELIVERIES COLUMN ===== --}}
    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <div class="px-4 py-3 border-b bg-blue-50 flex items-center gap-2">
            <x-heroicon-o-truck class="w-4 h-4 text-blue-600" />
            <span class="font-semibold text-sm text-blue-800">Deliveries</span>
            <span class="ml-auto text-xs text-blue-500 font-medium">{{ $deliveries->count() }} pending</span>
        </div>
        <table class="min-w-full text-xs text-left whitespace-nowrap">
            <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-600">
                <tr>
                    <th class="py-3 px-4 text-left">Product / Customer</th>
                    <th class="py-3 px-4 text-left">Address</th>
                    <th class="py-3 px-4 text-center">Date</th>
                    <th class="py-3 px-4 text-center text-blue-700">Driver</th>
                    <th class="py-3 px-3 text-center">Act.</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($deliveries as $op)
                <tr id="order-row-{{ $op->id }}" class="hover:bg-blue-50/30">
                    {{-- Product / Customer --}}
                    <td class="py-3 px-4">
                        <div class="font-medium text-gray-800 leading-tight">{{ $op->product_name }}</div>
                        <div class="text-gray-500">{{ $op->order?->customer_name ?? '—' }}</div>
                        <div class="mt-0.5">{!! $op->order?->view_link ?? '' !!}</div>
                    </td>
                    {{-- Address --}}
                    <td class="py-3 px-4 text-gray-500 max-w-[180px] whitespace-normal leading-tight">
                        {{ $op->order?->shippingAddress?->address }}{{ $op->order?->shippingAddress?->address ? ', ' : '' }}{{ $op->order?->shippingAddress?->city ?? '—' }}
                    </td>
                    {{-- Date --}}
                    <td class="py-3 px-4 text-center">
                        @php $iconColor = $op->delivery_status === 'Completed' ? 'text-green-600' : 'text-yellow-500'; @endphp
                        <div class="flex flex-col items-center">
                            <div class="flex items-center gap-1">
                                @if ($op->delivery_transport_mode === 'Truck')
                                    <x-heroicon-o-truck class="w-3.5 h-3.5 {{ $iconColor }}" />
                                @else
                                    <x-heroicon-o-building-storefront class="w-3.5 h-3.5 {{ $iconColor }}" />
                                @endif
                                <span>{{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date, 'M d, y') : 'N/A' }}</span>
                            </div>
                            <span class="text-gray-400">{{ $op->delivery_time ? \App\Helpers\CustomHelper::formatTime($op->delivery_time) : '' }}</span>
                        </div>
                    </td>
                    {{-- Driver --}}
                    <td class="py-3 px-4 text-center">
                        @if ($op->deliveryEmployee)
                            <button type="button" class="assign-driver-btn text-xs font-semibold text-blue-700 hover:underline"
                                data-slot="delivery"
                                data-order-product-unique-id="{{ $op->unique_id }}"
                                data-order-unique-id="{{ $op->order?->unique_id }}"
                                data-order-number="{{ $op->order?->order_number }}"
                                data-customer-name="{{ $op->order?->customer_name }}"
                                data-product-name="{{ $op->product_name }}"
                                data-delivery-date="{{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date, 'M d, y') : '' }}"
                                data-current-driver-id="{{ $op->delivery_by }}"
                                data-current-driver-name="{{ $op->deliveryEmployee->full_name }}">{{ $op->deliveryEmployee->full_name }}</button>
                        @else
                            <button type="button" class="assign-driver-btn text-xs text-blue-500 hover:text-blue-700 font-medium"
                                data-slot="delivery"
                                data-order-product-unique-id="{{ $op->unique_id }}"
                                data-order-unique-id="{{ $op->order?->unique_id }}"
                                data-order-number="{{ $op->order?->order_number }}"
                                data-customer-name="{{ $op->order?->customer_name }}"
                                data-product-name="{{ $op->product_name }}"
                                data-delivery-date="{{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date, 'M d, y') : '' }}"
                                data-current-driver-id="" data-current-driver-name="">
                                @if ($op->delivery_transport_mode === 'Truck')
                                    <x-heroicon-o-truck class="w-3 h-3 inline" />
                                @else
                                    <x-heroicon-o-building-storefront class="w-3 h-3 inline" />
                                @endif
                                Assign
                            </button>
                        @endif
                    </td>
                    {{-- Actions --}}
                    <td class="py-3 px-3 text-center">
                        <div class="flex gap-2 justify-center">
                            <a href="{{ route('admin.order-management.orders.edit', $op->order?->unique_id ?? 0) }}" class="text-sky-600 hover:text-sky-800" title="View Order">
                                <x-heroicon-o-eye class="w-4 h-4" />
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-8 text-center text-gray-400 text-sm italic">No pending deliveries</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===== RETURNS COLUMN ===== --}}
    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <div class="px-4 py-3 border-b bg-purple-50 flex items-center gap-2">
            <x-heroicon-o-arrow-uturn-left class="w-4 h-4 text-purple-600" />
            <span class="font-semibold text-sm text-purple-800">Returns</span>
            <span class="ml-auto text-xs text-purple-500 font-medium">{{ $returns->count() }} pending</span>
        </div>
        <table class="min-w-full text-xs text-left whitespace-nowrap">
            <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-600">
                <tr>
                    <th class="py-3 px-4 text-left">Product / Customer</th>
                    <th class="py-3 px-4 text-left">Address</th>
                    <th class="py-3 px-4 text-center">Date</th>
                    <th class="py-3 px-4 text-center text-purple-700">Driver</th>
                    <th class="py-3 px-3 text-center">Act.</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($returns as $op)
                <tr id="order-row-ret-{{ $op->id }}" class="hover:bg-purple-50/30">
                    {{-- Product / Customer --}}
                    <td class="py-3 px-4">
                        <div class="font-medium text-gray-800 leading-tight">{{ $op->product_name }}</div>
                        <div class="text-gray-500">{{ $op->order?->customer_name ?? '—' }}</div>
                        <div class="mt-0.5">{!! $op->order?->view_link ?? '' !!}</div>
                    </td>
                    {{-- Address --}}
                    <td class="py-3 px-4 text-gray-500 max-w-[180px] whitespace-normal leading-tight">
                        {{ $op->order?->shippingAddress?->address }}{{ $op->order?->shippingAddress?->address ? ', ' : '' }}{{ $op->order?->shippingAddress?->city ?? '—' }}
                    </td>
                    {{-- Date --}}
                    <td class="py-3 px-4 text-center">
                        @php $iconColor = $op->pickup_status === 'Completed' ? 'text-green-600' : 'text-yellow-500'; @endphp
                        <div class="flex flex-col items-center">
                            <div class="flex items-center gap-1">
                                @if ($op->pickup_transport_mode === 'Truck')
                                    <x-heroicon-o-truck class="w-3.5 h-3.5 {{ $iconColor }}" />
                                @else
                                    <x-heroicon-o-building-storefront class="w-3.5 h-3.5 {{ $iconColor }}" />
                                @endif
                                <span>{{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date, 'M d, y') : 'N/A' }}</span>
                            </div>
                            <span class="text-gray-400">{{ $op->pickup_time ? \App\Helpers\CustomHelper::formatTime($op->pickup_time) : '' }}</span>
                        </div>
                    </td>
                    {{-- Driver --}}
                    <td class="py-3 px-4 text-center">
                        @if ($op->pickupEmployee)
                            <button type="button" class="assign-driver-btn text-xs font-semibold text-purple-700 hover:underline"
                                data-slot="return"
                                data-order-product-unique-id="{{ $op->unique_id }}"
                                data-order-unique-id="{{ $op->order?->unique_id }}"
                                data-order-number="{{ $op->order?->order_number }}"
                                data-customer-name="{{ $op->order?->customer_name }}"
                                data-product-name="{{ $op->product_name }}"
                                data-delivery-date="{{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date, 'M d, y') : '' }}"
                                data-current-driver-id="{{ $op->pickup_by }}"
                                data-current-driver-name="{{ $op->pickupEmployee->full_name }}">{{ $op->pickupEmployee->full_name }}</button>
                        @else
                            <button type="button" class="assign-driver-btn text-xs text-purple-500 hover:text-purple-700 font-medium"
                                data-slot="return"
                                data-order-product-unique-id="{{ $op->unique_id }}"
                                data-order-unique-id="{{ $op->order?->unique_id }}"
                                data-order-number="{{ $op->order?->order_number }}"
                                data-customer-name="{{ $op->order?->customer_name }}"
                                data-product-name="{{ $op->product_name }}"
                                data-delivery-date="{{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date, 'M d, y') : '' }}"
                                data-current-driver-id="" data-current-driver-name="">
                                @if ($op->pickup_transport_mode === 'Truck')
                                    <x-heroicon-o-truck class="w-3 h-3 inline" />
                                @else
                                    <x-heroicon-o-building-storefront class="w-3 h-3 inline" />
                                @endif
                                Assign
                            </button>
                        @endif
                    </td>
                    {{-- Actions --}}
                    <td class="py-3 px-3 text-center">
                        <div class="flex gap-2 justify-center">
                            <a href="{{ route('admin.order-management.orders.edit', $op->order?->unique_id ?? 0) }}" class="text-sky-600 hover:text-sky-800" title="View Order">
                                <x-heroicon-o-eye class="w-4 h-4" />
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-8 text-center text-gray-400 text-sm italic">No pending returns</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

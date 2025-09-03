<div class="bg-white shadow-sm rounded-lg overflow-x-auto">
    <table class="min-w-full text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider border-b">
            <tr>
                <th class="px-4 py-3 text-left">Product</th>
                <th class="px-4 py-3 text-center">Order</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-left">Company</th>
                <th class="px-4 py-3 text-left">Delivery Address</th>
                <th class="px-4 py-3 text-left">Phone</th>
                <th class="px-4 py-3 text-center">Equipment</th>
                <th class="px-4 py-3 text-center">Delivery Date</th>
                <th class="px-4 py-3 text-center">Return Date</th>
                <th class="px-4 py-3 text-center">Payment</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <div id="schedule-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($orderProducts as $orderProduct)
                <tr id="order-row-{{ $orderProduct->id }}" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-left min-w-3xs max-w-3xs">{{ $orderProduct->product_name }}</td>
                    <td class="px-4 py-3 text-center">
                        {!! $orderProduct->order->view_link !!}
                    </td>
                    <td class="px-4 py-3 text-left">
                        <div class="font-medium">{{ $orderProduct->order->customer_name }}</div>
                        {{-- <div class="text-gray-500 text-xs">{{ $orderProduct->order->customer?->unique_id ?? '-' }}</div> --}}
                    </td>
                    <td class="px-4 py-3">
                        {{ $orderProduct->order->customer->company_name }}
                        @if (!empty($orderProduct->order->customer->company_website))
                            <a href="{{ $orderProduct->order->customer->company_website }}" target="_blank">
                                <div class="text-sm text-brand-500 flex items-center gap-1">
                                    <x-heroicon-o-globe-alt class="w-4 h-4 text-brand-400" />
                                    <span>{{ $orderProduct->order->customer->company_website }}</span>
                                </div>
                            </a>
                        @endif
                    </td>
                    <td class="px-4 py-3 truncate min-w-xs max-w-xs">
                        {{ $orderProduct->order->shippingAddress->full_address }}</td>
                    <td class="px-4 py-3 text-left ">{{ $orderProduct->order->shippingAddress->phone }}</td>
                    <td class="px-4 py-3 text-center">
                        <button type="button" class="text-blue-600 underline equipment-assign-btn"
                            data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                            data-order-product-name="{{ $orderProduct->product_name }}"
                            data-order="{{ $orderProduct?->order?->order_number }}">
                            {{ $orderProduct->equipment_details['equipment_name'] ?? 'Assign' }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-center">
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
                    <td class="px-4 py-3 text-center">
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

                    <td class="px-4 py-3 text-center">
                        {!! \App\Helpers\CustomHelper::statusBadge($orderProduct->order->last_payment_status) !!}
                    </td>
                    <td class="px-4 py-3">
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
                    <td colspan="10" class="text-center text-sm text-gray-500 px-4 py-6">
                        No Schedules found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Equipment Assign Modal -->
    <div id="equipmentAssignModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 id="addressModalTitle" class="text-lg font-semibold">Assign Equipment : <span
                        id="equipmentAssignModalTitle"></span></h2>
                <button type="button"
                    class="close-equipment-assign-modal text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'equipmentAssignForm',
                ])->open() }}

            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="user_unique_id">User</label>
                    {!! html()->select('user_unique_id', $employees)->id('user_unique_id')->class([
                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                        ])->required() !!}
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 required"
                        for="equipment_unique_id">Equipment</label>
                    <select name="equipment_unique_id" id="equipment_unique_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700">
                        <option value="" data-current-status="">Select Equipment</option>
                        @foreach ($equipments as $equipment)
                            <option value="{{ $equipment->unique_id }}"
                                data-current-status="{{ $equipment->current_status }}"
                                data-link="{{ $equipment->linkWithTitle()['link'] }}"
                                data-link-title="{{ $equipment->linkWithTitle()['title'] }}">
                                {{ $equipment->equipment_name }}</option>
                        @endforeach
                    </select>
                    <div class="flex items-center justify-between gap-3 mt-2">
                        <span id="equipment-status-display" class="text-sm font-semibold text-yellow-400"></span>
                        <a href="#" class="text-blue-600 hover:underline text-sm font-semibold"
                            id="equipment-page-link"></a>
                    </div>
                </div>
                <input type="hidden" id="order-product-unique-id" name="order_product_unique_id" value="">
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button"
                    class="close-equipment-assign-modal px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit" id="equipment-assign-submit"
                    class="px-6 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Assign
                </button>
            </div>
            </form>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $orderProducts->links('vendor.pagination.tailwind') }}
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('equipmentAssignModal');
                const closeBtn = document.querySelectorAll('.close-equipment-assign-modal');
                const assignBtns = document.querySelectorAll('.equipment-assign-btn');
                const equipmentAssignModalTitle = document.getElementById('equipmentAssignModalTitle');
                const equipmentAssignForm = document.getElementById('equipmentAssignForm');


                assignBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const orderProductUniqueId = this.getAttribute('data-order-product-unique-id');
                        const orderProductName = this.getAttribute('data-order-product-name');
                        const orderNumber = this.getAttribute('data-order');

                        document.getElementById('order-product-unique-id').value = orderProductUniqueId;
                        equipmentAssignModalTitle.textContent = orderNumber + ' ' + orderProductName;
                        modal.classList.remove('hidden');
                    });
                });

                closeBtn.forEach(btn => {
                    btn.addEventListener('click', function() {
                        clearModalFields();
                        modal.classList.add('hidden');
                    });
                });

                function clearModalFields() {
                    document.getElementById('user_unique_id').selectedIndex = 0;
                    document.getElementById('equipment_unique_id').selectedIndex = 0;
                    document.getElementById('order-product-unique-id').value = '';
                    document.getElementById('equipment-status-display').textContent = '';
                    document.getElementById('equipment-page-link').textContent = '';
                    if (equipmentAssignForm) {
                        equipmentAssignForm.reset();
                    }
                };

                const equipmentSelect = document.getElementById('equipment_unique_id');
                const assignBtn = document.getElementById('equipment-assign-submit');
                const statusDisplayId = 'equipment-status-display';
                const equipmentPageLinkId = 'equipment-page-link';


                // Function to update status and button
                function updateEquipmentStatus() {
                    const selectedOption = equipmentSelect.options[equipmentSelect.selectedIndex];
                    const status = selectedOption.getAttribute('data-current-status');
                    const link = selectedOption.getAttribute('data-link');
                    const title = selectedOption.getAttribute('data-link-title');
                    const statusDiv = document.getElementById(statusDisplayId);
                    const pageLink = document.getElementById(equipmentPageLinkId);

                    if (!status) {
                        statusDiv.textContent = '';
                        statusDiv.className = 'mt-2 text-sm font-semibold text-gray-600';
                        assignBtn.disabled = true;
                        pageLink.href = '';
                        pageLink.textContent = '';
                        return;
                    }
                    let statusText = '';
                    let statusColor = 'text-gray-600';
                    let isAvailable = true;

                    switch (status) {
                        case 'available':
                            statusText = 'Available';
                            statusColor = 'text-green-600';
                            isAvailable = true;

                            break;
                        case 'damaged':
                            statusText = 'Not Available';
                            statusColor = 'text-red-600';
                            isAvailable = false;
                            break;
                        case 'maintenance':
                            statusText = 'Maint. Hold';
                            statusColor = 'text-yellow-600';
                            isAvailable = false;
                            break;
                        default:
                            statusText = status ? status : '';
                            statusColor = 'text-gray-600';
                            isAvailable = true;
                    }

                    statusDiv.textContent = `Status: ${statusText}`;
                    statusDiv.className = `mt-2 text-sm font-semibold ${statusColor}`;
                    assignBtn.disabled = !isAvailable;
                    pageLink.href = link;
                    pageLink.textContent = title;
                }

                equipmentSelect.addEventListener('change', updateEquipmentStatus);

                // Initial status update
                updateEquipmentStatus();


                document.getElementById('equipmentAssignForm').addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (!$(equipmentAssignForm).parsley().isValid()) {
                        $(equipmentAssignForm).parsley().validate();
                        return;
                    }
                    const form = this;
                    const submitBtn = document.getElementById('equipment-assign-submit');
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Assigning...';

                    const formData = new FormData(form);

                    apiFetch('{{ route('admin.order-management.schedules.assign-equipment') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(data => {
                            if (data.success) {
                                modal.classList.add('hidden');
                                notyf.success(data.message);
                                clearModalFields();
                                window.location.reload();
                            } else {
                                notyf.error(data.message);
                            }
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Assign';
                        });
                });
            });
        </script>
    @endpush

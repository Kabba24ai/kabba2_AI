@extends('admin.layouts.app')

@section('title', 'Schedules')

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

@section('content')

    @include('flash::message')

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold flex items-center gap-2">
            <x-heroicon-o-calendar-days class="w-6 h-6 text-blue-600" />
            Schedule Management
        </h1>
        <a href="{{ route('admin.order-management.schedules.index') }}"
            class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium text-md flex items-center gap-2">
            <x-heroicon-o-arrow-path class="w-5 h-5" />
            Reload
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm space-y-4">
        <!-- Row 1: Inputs & Selects -->
        <div class="flex flex-wrap gap-4 items-center">
            <div class="w-full sm:w-auto">
                <button type="button" id="clear-filters"
                    class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    Clear
                </button>
            </div>

            <div class="w-full sm:w-48">
                <div class="relative bg-white">
                    <input type="text" id="customer_name" placeholder="Customer name" name="customer_name"
                        value="{{ request('customer_name') }}"
                        class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass
                        class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div class="w-full sm:w-48">
                <div class="relative bg-white">
                    <input type="text" id="customer_company_name" placeholder="Customer company"
                        name="customer_company_name" value="{{ request('customer_company_name') }}"
                        class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass
                        class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            {{-- Search Phone --}}
            <div>
                {{-- <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-1">Search Phone</label> --}}
                <div class="relative bg-white">
                    <input type="text" id="customer_phone" placeholder="(xxx) xxx-xxxx" name="customer_phone"
                        value="{{ request('customer_phone') }}"
                        class="masked-phone pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-38 focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-phone class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div class="w-full sm:w-35">
                <div class="relative bg-white">
                    <input type="text" id="order_number" placeholder="Order ID" name="order_number"
                        value="{{ request('order_number') }}"
                        class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass
                        class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            {{-- Category --}}
            <div class="w-full sm:w-48">
                {{-- <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label> --}}
                <select name="category"
                    class="choices-select py-3 px-3 w-full rounded-md border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    <option value="">Select Category</option>
                    @foreach ($categories as $id => $category)
                        <option value="{{ $id }}" @selected(request('category') == $id)>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Payment Method --}}
            <div>
                {{-- <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Type</label> --}}
                <select id="payment_method" name="payment_method"
                    class= " border bg-white border-gray-300 rounded-md py-3 px-3 text-sm w-38 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Payment Types</option>
                    <option value="Card" @selected(request('payment_method') == 'Card')>Card</option>
                    <option value="COD" @selected(request('payment_method') == 'COD')>POD</option>
                    <option value="Account" @selected(request('payment_method') == 'Account')>Account</option>
                </select>
            </div>

            {{-- Payment Status --}}
            <div>
                {{-- <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment</label> --}}
                <select id="payment_status" name="payment_status"
                    class=" border bg-white border-gray-300 rounded-md py-3 px-3 text-sm w-34 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Payments</option>
                    <option value="Paid" @selected(request('payment_status') == 'Paid')>Paid</option>
                    <option value="Pending" @selected(request('payment_status') == 'Pending')>Pending</option>
                    <option value="Account" @selected(request('payment_status') == 'Account')>Account</option>
                    <option value="Partial Refund" @selected(request('payment_status') == 'Partial Refund')>Partial Refund</option>
                    <option value="Refunded" @selected(request('payment_status') == 'Refunded')>Refunded</option>
                    <option value="Failed" @selected(request('payment_status') == 'Failed')>Failed</option>
                </select>
            </div>

            <!-- Date Dropdown -->
            <div>
                {{-- <label for="date_filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Date</label> --}}
                <select name="date_filter" id="date_filter"
                    class=" border border-gray-300 rounded-md py-3 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    <option value="">All Dates</option>
                    <option value="today" @selected(request('date_filter') == 'today')>Today</option>
                    <option value="week" @selected(request('date_filter') == 'week')>This Week</option>
                    <option value="month" @selected(request('date_filter') == 'month')>This Month</option>
                </select>
            </div>
        </div>
        @php
            // If param is not set at all, default both checked. If set (even empty array), use only what is present.
            $transportMode = request()->input('transport_mode');
            $scheduleType = request()->input('schedule_type');
            $storeLocation = request()->input('store_location');
        @endphp

        <!-- Row 2: Checkboxes & Date Filter -->
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <div class="flex flex-wrap items-center gap-6">
                <!-- Schedule Type -->
                <div class="flex items-center gap-2 bg-white rounded-md px-4 py-2 border">
                    <x-heroicon-o-calendar class="w-5 h-5 text-blue-500" />
                    <span class="font-medium">Schedule Type</span>
                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" value="Delivery" name="schedule_type[]"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" @checked(is_array($scheduleType) ? in_array('Delivery', $scheduleType) : true)>
                        Delivery
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" value="Return" name="schedule_type[]"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" @checked(is_array($scheduleType) ? in_array('Return', $scheduleType) : true)>
                        Return
                    </label>
                </div>

                <!-- Transport Mode -->
                <div class="flex items-center gap-2 bg-white rounded-md px-4 py-2 border">
                    <x-heroicon-o-truck class="w-5 h-5 text-green-500" />
                    <span class="font-medium">Delivery</span>

                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" name="transport_mode[]" value="Truck"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300"
                            @checked(is_array($transportMode) ? in_array('Truck', $transportMode) : true)>
                        Truck
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="transport_mode[]" value="Store"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300"
                            @checked(is_array($transportMode) ? in_array('Store', $transportMode) : true)>
                        In Store
                    </label>
                </div>
                <!-- Store Locations -->
                <div class="flex items-center gap-2 bg-white rounded-md px-4 py-2 border">
                    <x-heroicon-o-map-pin class="w-5 h-5 text-purple-500" />
                    <span class="font-medium">Store</span>
                    @foreach ($stores as $store)
                        <label class="flex items-center gap-1 {{ $loop->first ? 'ml-2' : '' }}">
                            <input type="checkbox" name="store_location[]" value="{{ $store->id }}"
                                id="store_location_{{ Str::slug($store->store_name, '_') }}"
                                class="text-blue-600 focus:ring-blue-500 rounded border-gray-300"
                                @checked(is_array($storeLocation) ? in_array($store->id, $storeLocation) : true)>
                            {{ $store->store_name }}
                        </label>
                    @endforeach
                </div>
                <!-- Special Filters -->
                <div class="flex items-center gap-2 bg-white rounded-md px-4 py-2 border">
                    <x-heroicon-o-funnel class="w-5 h-5 text-orange-500" />
                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" name="rescheduled_only" value="Reschedule" id="rescheduled_only"
                            class="text-red-600 focus:ring-red-500 rounded border-gray-300" @checked(request('rescheduled_only') == 'Reschedule')>
                        <span class="font-medium">Rescheduled Pending : {{ $rescheduleOrder }}</span>
                    </label>
                </div>
                <!-- Quick Filter Buttons -->
                <div class="flex gap-2">
                    <button type="button"
                        class="schedule-filter-btn px-3 py-2 rounded bg-blue-100 text-blue-700 text-xs font-semibold hover:bg-blue-200 border border-blue-200"
                        data-schedule-type="Delivery" data-transport-mode="Truck">Deliveries - Truck</button>
                    <button type="button"
                        class="schedule-filter-btn px-3 py-2 rounded bg-green-100 text-green-700 text-xs font-semibold hover:bg-green-200 border border-green-200"
                        data-schedule-type="Delivery" data-transport-mode="Store">Deliveries - In Store</button>
                    <button type="button"
                        class="schedule-filter-btn px-3 py-2 rounded bg-purple-100 text-purple-700 text-xs font-semibold hover:bg-purple-200 border border-purple-200"
                        data-schedule-type="Return" data-transport-mode="Truck">Returns - Truck</button>
                    <button type="button"
                        class="schedule-filter-btn px-3 py-2 rounded bg-indigo-100 text-indigo-700 text-xs font-semibold hover:bg-indigo-200 border border-indigo-200"
                        data-schedule-type="Return" data-transport-mode="Store">Returns - In Store</button>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto py-6">
        <div id="schedule-table-wrapper">
            @include('admin.order_management.schedules.partials._table', [
                'orderProducts' => [],
            ])
        </div>
    </div>

    <!-- Equipment Assign Modal -->
    <div id="equipmentAssignModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center px-4">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="relative px-6 pt-6 pb-4 border-b">
                <h2 class="text-xl font-semibold text-gray-900 text-center">Assign Equipment</h2>
                <button type="button"
                    class="close-equipment-assign-modal text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none absolute right-6 top-6">&times;</button>
            </div>

            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'equipmentAssignForm',
                ])->open() }}

            <div class="px-4 pt-3 space-y-2 overflow-y-auto">
                <!-- Order Information Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-2">
                    <div>
                        <span class="text-xs font-semibold text-blue-700">Order ID:</span>
                        <a id="assign-order-id" href="#" class="text-sm text-blue-900 font-semibold">-</a>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-blue-700">Customer:</span>
                        <span id="assign-customer-name" class="text-sm text-blue-900 font-semibold">-</span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-blue-700">Product Ordered:</span>
                        <span id="assign-product-name" class="text-sm text-blue-900 font-semibold">-</span>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700 required" for="user_unique_id">User</label>
                    <select name="user_unique_id" id="user_unique_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700"
                        required>
                        <option value="">Select Employee</option>
                        @foreach ($employees as $employeeId => $employeeName)
                            <option value="{{ $employeeId }}">{{ $employeeName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700 required">Category</label>
                    <select id="category_select"
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm bg-white text-gray-700">
                        <option value="">Select Category</option>
                    </select>
                </div>


                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700 required" for="equipment_unique_id">Equipment</label>
                    <select name="equipment_unique_id" id="equipment_unique_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700">
                        <option value="" data-current-status="">Select Equipment</option>
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
                    class="close-equipment-assign-modal px-6 py-3 rounded-lg font-medium text-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit" id="equipment-assign-submit"
                    class="px-6 py-3 rounded-lg font-medium text-md bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                    Assign
                </button>
            </div>
            </form>
        </div>
    </div>

    <div id="scheduleAssistantModal" class="fixed inset-0 z-9999 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeScheduleAssistantModal()"></div>

        <div
            class="absolute left-1/2 top-1/2 w-full max-w-4xl -translate-x-1/2 -translate-y-1/2 rounded-xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b px-6 py-4">
                <h2 id="scheduleAssistantModalTitle" class="text-lg font-semibold">Scheduling Assistant</h2>
                <button type="button" class="text-gray-500 hover:text-gray-700"
                    onclick="closeScheduleAssistantModal()">✕</button>
            </div>

            <div id="scheduleAssistantContent" class="max-h-[70vh] overflow-y-auto p-6">
                <p class="text-sm text-gray-500">Loading...</p>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        function escapeScheduleHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showScheduleAssistantModal(title = 'Scheduling Assistant') {
            const modal = document.getElementById('scheduleAssistantModal');
            const content = document.getElementById('scheduleAssistantContent');
            const modalTitle = document.getElementById('scheduleAssistantModalTitle');

            modal.classList.remove('hidden');
            modalTitle.textContent = title;
            content.innerHTML = '<p class="text-sm text-gray-500">Loading...</p>';

            return content;
        }

        async function fetchScheduleAssistantJson(url, fallbackMessage) {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            const result = await response.json().catch(() => null);

            if (!response.ok) {
                throw new Error(result?.message || fallbackMessage);
            }

            return result;
        }

        function openAIScheduleAdvisorModal(orderProductId) {
            const content = showScheduleAssistantModal('AI Schedule Advisor');

            const url =
                '{{ route('admin.order-management.schedules.ai.show', ['orderProductId' => '__ORDER_PRODUCT_ID__']) }}'
                .replace('__ORDER_PRODUCT_ID__', orderProductId);

            fetchScheduleAssistantJson(url, 'Failed to load AI schedule advisor.')
                .then(result => {
                    if (!result.success) {
                        content.innerHTML = `
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            ${escapeScheduleHtml(result.message || result.error || 'Failed to load AI schedule advisor.')}
                        </div>
                    `;
                        return;
                    }

                    const assistant = result.data?.assistant || {};
                    const ai = result.data?.ai || {};
                    const recommendation = ai.recommendation || {};

                    const issuesHtml = (assistant.issues || []).map(issue => `
                    <div class="mb-2 rounded-lg border border-yellow-200 bg-yellow-50 p-3">
                        <div class="font-medium text-yellow-800">${escapeScheduleHtml(issue.code)}</div>
                        <div class="text-sm text-yellow-700">${escapeScheduleHtml(issue.message)}</div>
                    </div>
                `).join('');

                    const reasoningHtml = (recommendation.reasoning || []).map(reason => `
                    <li>${escapeScheduleHtml(reason)}</li>
                `).join('');

                    const actionsHtml = (recommendation.actions_required || []).map(action => `
                    <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700">${escapeScheduleHtml(action)}</span>
                `).join('');

                    const warningsHtml = (recommendation.warnings || []).map(warning => `
                    <li>${escapeScheduleHtml(warning)}</li>
                `).join('');

                    const alternativesHtml = (recommendation.alternatives || []).map(option => `
                    <div class="rounded-xl border p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base font-semibold">${escapeScheduleHtml(option.equipment_name || 'No equipment selected')}</h3>
                                <p class="text-sm text-gray-500">#${escapeScheduleHtml(option.equipment_id ?? '-')}</p>
                            </div>
                            <span class="text-xs uppercase tracking-wide text-gray-500">${escapeScheduleHtml(option.relationship_type)}</span>
                        </div>
                        <p class="mt-3 text-sm text-gray-700">${escapeScheduleHtml(option.summary)}</p>
                        ${(option.actions_required || []).length ? `
                                <div class="mt-3 flex flex-wrap gap-2">
                                    ${option.actions_required.map(action => `
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-xs text-amber-700">${escapeScheduleHtml(action)}</span>
                                `).join('')}
                                </div>
                            ` : ''}
                    </div>
                `).join('');

                    content.innerHTML = `
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-lg bg-gray-50 p-4">
                                <div class="text-xs uppercase tracking-wide text-gray-500">Delivery</div>
                                <div class="font-medium">${escapeScheduleHtml(assistant.order_window?.delivery || '-')}</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4">
                                <div class="text-xs uppercase tracking-wide text-gray-500">Pickup</div>
                                <div class="font-medium">${escapeScheduleHtml(assistant.order_window?.pickup || '-')}</div>
                            </div>
                        </div>

                        ${issuesHtml ? `<div><h3 class="mb-3 text-lg font-semibold">Operational Issues</h3>${issuesHtml}</div>` : ''}

                        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-sky-700">Decision</div>
                                    <h3 class="mt-1 text-lg font-semibold text-sky-900">${escapeScheduleHtml(recommendation.decision || 'No decision')}</h3>
                                    <p class="mt-1 text-sm text-sky-800">${escapeScheduleHtml(recommendation.recommended_equipment_name || 'No equipment recommended')}</p>
                                </div>
                                <div class="text-right text-sm text-sky-800">
                                    <div>Equipment ID: ${escapeScheduleHtml(recommendation.recommended_equipment_id ?? '-')}</div>
                                    <div>Relationship: ${escapeScheduleHtml(recommendation.relationship_type || 'unknown')}</div>
                                </div>
                            </div>

                            ${actionsHtml ? `<div class="mt-4 flex flex-wrap gap-2">${actionsHtml}</div>` : ''}
                        </div>

                        <div>
                            <h3 class="mb-3 text-lg font-semibold">AI Reasoning</h3>
                            ${reasoningHtml ? `<ul class="list-disc space-y-2 pl-5 text-sm text-gray-700">${reasoningHtml}</ul>` : '<p class="text-sm text-gray-500">No reasoning returned.</p>'}
                        </div>

                        ${warningsHtml ? `
                                <div>
                                    <h3 class="mb-3 text-lg font-semibold text-amber-800">Warnings</h3>
                                    <ul class="list-disc space-y-2 pl-5 text-sm text-amber-700">${warningsHtml}</ul>
                                </div>
                            ` : ''}

                        <div>
                            <h3 class="mb-3 text-lg font-semibold">Alternatives</h3>
                            <div class="space-y-4">
                                ${alternativesHtml || '<p class="text-sm text-gray-500">No alternatives returned.</p>'}
                            </div>
                        </div>

                        ${ai.error ? `
                                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                    ${escapeScheduleHtml(ai.error)}
                                </div>
                            ` : ''}
                    </div>
                `;
                })
                .catch(error => {
                    content.innerHTML = `
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        ${escapeScheduleHtml(error.message || 'Something went wrong while loading AI schedule advisor data.')}
                    </div>
                `;
                });
        }

        function closeScheduleAssistantModal() {
            document.getElementById('scheduleAssistantModal').classList.add('hidden');
        }
    </script>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            let customerNameInput = document.querySelector('input[name="customer_name"]');
            let customerCompanyNameInput = document.querySelector('input[name="customer_company_name"]');
            let customerPhoneInput = document.querySelector('input[name="customer_phone"]');
            let orderNumberInput = document.querySelector('input[name="order_number"]');
            let categoryInput = document.querySelector('select[name="category"]');
            let paymentStatusInput = document.querySelector('select[name="payment_status"]');
            let paymentMethodInput = document.querySelector('select[name="payment_method"]');
            let dateFilterInput = document.querySelector('select[name="date_filter"]');
            let storeLocationInputs = document.querySelectorAll('input[name="store_location[]"]');
            let transportModeInputs = document.querySelectorAll('input[name="transport_mode[]"]');
            let scheduleTypeInputs = document.querySelectorAll('input[name="schedule_type[]"]');
            let rescheduledOnlyInput = document.querySelector('input[name="rescheduled_only"]');
            let loadingIndicator = document.querySelector('#schedule-loading');
            let wrapper = document.querySelector('#schedule-table-wrapper');
            let timeout = null;
            let urlScheduleType = '{{ $urlScheduleType }}';
            let urlTransportMode = '{{ $urlTransportMode }}';

            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location
                    .search)
                .get('per_page') || null;
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;



            const screenKey = "schedules_filters";

            const fieldMap = {
                'customer_name': customerNameInput,
                'customer_company_name': customerCompanyNameInput,
                'customer_phone': customerPhoneInput,
                'order_number': orderNumberInput,
                'category': categoryInput,
                'payment_status': paymentStatusInput,
                'payment_method': paymentMethodInput,
                'date_filter': dateFilterInput,
                'store_location[]': storeLocationInputs,
                'rescheduled_only': rescheduledOnlyInput,
                'schedule_type[]': scheduleTypeInputs,
                'transport_mode[]': transportModeInputs,
            };

            // Clear filters functionality using global clearFilters
            document.getElementById('clear-filters').addEventListener('click', function() {
                window.clearFilters(fieldMap, screenKey);
                fetchSchedules();
            });



            // Load saved filters on page load
            FilterFreezer.loadFilters(screenKey, fieldMap);

            if (urlScheduleType) {
                scheduleTypeInputs.forEach(input => {
                    const mode = input.value;
                    if (urlScheduleType.includes(mode)) {
                        input.checked = true;
                    } else {
                        input.checked = false;
                    }

                     storeLocationInputs.forEach(input => {
        input.checked = true;
    });

     // AUTO SELECT TODAY
    if (dateFilterInput) {
        dateFilterInput.value = 'today';
    }

                });
            }

            if (urlTransportMode) {
                transportModeInputs.forEach(input => {
                    const mode = input.value;
                    if (urlTransportMode.split(',').includes(mode)) {
                        input.checked = true;
                    }else {
                        input.checked = false;
                    }
                    
                });
            }
            fetchSchedules(pageParam, perPageParam); // initial fetch after loading saved filters
            function fetchSchedules(page = 1, perPage = 30) {
                const params = new URLSearchParams();

                if (customerNameInput && (customerNameInput.value.length >= 3 || customerNameInput.value.length ===
                        0)) params.append('customer_name', customerNameInput.value);
                if (customerCompanyNameInput && (customerCompanyNameInput.value.length >= 3 ||
                        customerCompanyNameInput.value
                        .length === 0)) params.append('customer_company_name', customerCompanyNameInput.value);
                if (customerPhoneInput && (customerPhoneInput.value.length >= 3 || customerPhoneInput.value
                        .length === 0)) params.append('customer_phone', customerPhoneInput.value);
                if (orderNumberInput && (orderNumberInput.value.length >= 1 || orderNumberInput.value.length === 0))
                    params.append('order_number', orderNumberInput.value);
                if (categoryInput && categoryInput.value) params.append('category', categoryInput.value);
                if (paymentStatusInput && paymentStatusInput.value) params.append('payment_status',
                    paymentStatusInput.value);
                if (paymentMethodInput && paymentMethodInput.value) params.append('payment_method',
                    paymentMethodInput.value);
                if (dateFilterInput && dateFilterInput.value) params.append('date_filter', dateFilterInput.value);
                if (rescheduledOnlyInput && rescheduledOnlyInput.checked) params.append('rescheduled_only',
                    rescheduledOnlyInput.value);
                if (perPage) params.append('per_page', perPage);
                params.set('page', page); // Set the current page

                // --- START: Handle schedule_type[] checkboxes ---
                let anyScheduleTypeChecked = false;

                scheduleTypeInputs.forEach(input => {
                    if (input.checked) {
                        params.append('schedule_type[]', input.value);
                        anyScheduleTypeChecked = true;
                    }
                });
                // If none are checked, send an empty value so backend knows it's user intent
                if (!anyScheduleTypeChecked) {
                    params.append('schedule_type[]', false);
                }
                // --- END: Handle schedule_type[] checkboxes ---

                // --- START: Handle transport_mode[] checkboxes ---
                let anyTransportModeChecked = false;
                transportModeInputs.forEach(input => {
                    if (input.checked) {
                        params.append('transport_mode[]', input.value);
                        anyTransportModeChecked = true;
                    }
                });
                // If none are checked, send an empty value so backend knows it's user intent
                if (!anyTransportModeChecked) {
                    params.append('transport_mode[]', false);
                }
                // --- END: Handle transport_mode[] checkboxes ---

                // --- START: Handle store_location[] checkboxes ---
                let anyStoreLocationChecked = false;
                storeLocationInputs.forEach(input => {
                    if (input.checked) {
                        params.append('store_location[]', input.value);
                        anyStoreLocationChecked = true;
                    }
                });
                // If none are checked, send an empty value so backend knows it's user intent
                if (!anyStoreLocationChecked) {
                    params.append('store_location[]', false);
                }
                // --- END: Handle store_location[] checkboxes ---

                // Show loader
                loadingIndicator.classList.remove('hidden');

                // Save current filters
                FilterFreezer.saveFilters(screenKey, fieldMap);
                wrapper.classList.add('opacity-50', 'pointer-events-none');

                apiFetch("{{ route('admin.order-management.schedules.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        wrapper.innerHTML = response.html;
                    })
                    .finally(() => {
                        loadingIndicator.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }

            // Register pagination
            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchSchedules
            });

            if (customerNameInput) customerNameInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchSchedules, 400);
            });
            if (customerCompanyNameInput) customerCompanyNameInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchSchedules, 400);
            });
            if (customerPhoneInput) customerPhoneInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchSchedules, 400);
            });
            if (orderNumberInput) orderNumberInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchSchedules, 400);
            });
            if (categoryInput) categoryInput.addEventListener('change', fetchSchedules);
            if (paymentStatusInput) paymentStatusInput.addEventListener('change', fetchSchedules);
            if (paymentMethodInput) paymentMethodInput.addEventListener('change', fetchSchedules);
            if (dateFilterInput) dateFilterInput.addEventListener('change', fetchSchedules);
            storeLocationInputs.forEach(input => input.addEventListener('change', fetchSchedules));
            if (rescheduledOnlyInput) rescheduledOnlyInput.addEventListener('change', fetchSchedules);

            transportModeInputs.forEach(input => {
                input.addEventListener('change', function(e) {
                    let checked = Array.from(transportModeInputs).filter(i => i.checked);
                    if (checked.length === 0) {
                        e.preventDefault();
                        input.checked = true;
                    } else {
                        fetchSchedules();
                    }
                });
            });

            scheduleTypeInputs.forEach(input => {
                input.addEventListener('change', function(e) {
                    // Collect all checked checkboxes
                    let checked = Array.from(scheduleTypeInputs).filter(i => i.checked);
                    // If this action would uncheck the last one, prevent it
                    if (checked.length === 0) {
                        // Prevent unchecking the last one
                        e.preventDefault();
                        input.checked = true;
                    } else {
                        fetchSchedules();
                    }
                });
            });

            // Schedule filter buttons logic
            document.querySelectorAll('.schedule-filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const scheduleType = this.getAttribute('data-schedule-type');
                    const transportMode = this.getAttribute('data-transport-mode');

                    // Uncheck all schedule_type checkboxes, then check the right one
                    document.querySelectorAll('input[name="schedule_type[]"]').forEach(cb => {
                        cb.checked = (cb.value === scheduleType);
                    });
                    // Uncheck all transport_mode checkboxes, then check the right one
                    document.querySelectorAll('input[name="transport_mode[]"]').forEach(cb => {
                        cb.checked = (cb.value === transportMode);
                    });

                      // CHECK ALL STORES
                            document.querySelectorAll('input[name="store_location[]"]').forEach(cb => {
                                cb.checked = true;
                            });
                            // AUTO SELECT TODAY
                    if (dateFilterInput) {
                        dateFilterInput.value = 'today';
                    }

                    // Trigger AJAX filter
                    if (typeof fetchSchedules === 'function') fetchSchedules(pageParam,
                        perPageParam);
                });
            });

            const modal = document.getElementById('equipmentAssignModal');
            const equipmentAssignForm = document.getElementById('equipmentAssignForm');
            const categorySelect = document.getElementById('category_select');
            const equipmentSelect = document.getElementById('equipment_unique_id');
            const assignBtn = document.getElementById('equipment-assign-submit');
            const statusDisplayId = 'equipment-status-display';
            const equipmentPageLinkId = 'equipment-page-link';
            const orderIdLabel = document.getElementById('assign-order-id');
            const customerNameLabel = document.getElementById('assign-customer-name');
            const productNameLabel = document.getElementById('assign-product-name');

            let fullData = {}; // store categories + equipment

            // --- Event delegation for OPEN buttons (works after table refresh) ---
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.equipment-assign-btn');
                if (!btn) return;

                const orderProductUniqueId = btn.getAttribute('data-order-product-unique-id');
                const orderId = btn.dataset.orderId || '';
                const orderUniqueId = btn.dataset.orderUniqueId || '';
                const productName = btn.dataset.productName || '';
                const customerName = btn.dataset.customerName || '';
                let orderDetailUrl =
                    "{{ route('admin.order-management.orders.edit', ['unique_id' => 'ORDER_ID_PLACEHOLDER']) }}";

                document.getElementById('order-product-unique-id').value = orderProductUniqueId || '';

                if (orderIdLabel) {
                    orderIdLabel.textContent = orderId ? `#${orderId.replace(/^#/, '')}` : '-';
                    orderIdLabel.href = orderUniqueId ? orderDetailUrl.replace('ORDER_ID_PLACEHOLDER',
                        orderUniqueId) : '';
                }

                if (customerNameLabel) {
                    customerNameLabel.textContent = customerName || '-';
                }

                if (productNameLabel) {
                    productNameLabel.textContent = productName || '-';
                }

                modal.classList.remove('hidden');

                // Refresh status on open in case select kept previous state
                updateEquipmentStatus();
            });

            // --- Event delegation for CLOSE buttons (works after table refresh) ---
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.close-equipment-assign-modal');
                if (!btn) return;
                clearModalFields();
                modal.classList.add('hidden');
            });

            function clearModalFields() {
                const userSel = document.getElementById('user_unique_id');
                const equipSel = document.getElementById('equipment_unique_id');
                const orderInput = document.getElementById('order-product-unique-id');
                const statusDiv = document.getElementById('equipment-status-display');
                const pageLink = document.getElementById('equipment-page-link');

                if (userSel) userSel.selectedIndex = 0;
                if (equipSel) equipSel.selectedIndex = 0;
                if (orderInput) orderInput.value = '';
                if (orderIdLabel) orderIdLabel.textContent = '-';
                if (orderIdLabel) orderIdLabel.href = '';
                if (customerNameLabel) customerNameLabel.textContent = '-';
                if (productNameLabel) productNameLabel.textContent = '-';
                if (statusDiv) {
                    statusDiv.textContent = '';
                    statusDiv.className = 'text-sm font-semibold text-gray-600';
                }
                if (pageLink) {
                    pageLink.href = '';
                    pageLink.textContent = '';
                }
                if (equipmentAssignForm) {
                    equipmentAssignForm.reset();
                }

                // Disable assign button until a valid available option is chosen
                if (assignBtn) {
                    assignBtn.disabled = true;
                    assignBtn.textContent = 'Assign';
                }
            }

            categorySelect.addEventListener('change', function() {

                const selectedCatId = Number(this.value);
                equipmentSelect.innerHTML = '<option value="">Select Equipment</option>';

                let equipments = [];

                // If no category selected -> load ALL equipments
                if (!selectedCatId) {
                    fullData.forEach(cat => {
                        if (Array.isArray(cat.equipments)) {
                            equipments = equipments.concat(cat.equipments);
                        }
                    });
                } else {
                    // Only selected category
                    const category = fullData.find(c => c.id === selectedCatId);
                    if (!category) return;
                    equipments = category.equipments || [];
                }

                //  If category has NO equipment
                if (equipments.length === 0) {
                    const opt = document.createElement('option');
                    opt.textContent = 'No equipments';
                    opt.disabled = true;
                    opt.selected = true;
                    equipmentSelect.appendChild(opt);

                    updateEquipmentStatus();
                    return;
                }

                // Group by status
                const groups = {
                    maintenance: [],
                    rented: [],


                    damaged: [],
                    available: [],
                    other: []
                };

                equipments.forEach(equipment => {
                    const status = (equipment.current_status || '').toLowerCase();

                    if (status === 'available') groups.available.push(equipment);
                    else if (status === 'rented') groups.rented.push(equipment);
                    else if (status === 'damaged') groups.damaged.push(equipment);
                    else if (status === 'maintenance') groups.maintenance.push(equipment);
                    else groups.other.push(equipment);
                });

                // Append groups
                appendGroup('Available', groups.available);
                appendGroup('Maint. Hold', groups.maintenance);
                appendGroup('Damaged', groups.damaged);

                appendGroup('Rented', groups.rented);
                appendGroup('Other', groups.other);


                updateEquipmentStatus();
            });




            // Update status and button
            function updateEquipmentStatus() {
                const statusDiv = document.getElementById(statusDisplayId);
                const pageLink = document.getElementById(equipmentPageLinkId);

                if (!equipmentSelect || !statusDiv || !assignBtn || !pageLink) return;

                const selectedOption = equipmentSelect.options[equipmentSelect.selectedIndex] || {};
                const status = selectedOption.getAttribute?.('data-current-status');
                const link = selectedOption.getAttribute?.('data-link') || '';
                const title = selectedOption.getAttribute?.('data-link-title') || '';

                if (!status) {
                    statusDiv.textContent = '';
                    statusDiv.className = 'text-sm font-semibold text-gray-600';
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

                    case 'rented':
                        statusText = 'Rented';
                        statusColor = 'text-gray-600';
                        isAvailable = true;

                        // SweetAlert message for rented items
                        // window.showError(
                        //     "This item is currently Rented, so it cannot be assigned to this Order.",
                        //     "Rented "
                        // );
                        // equipmentSelect.selectedIndex = 0;
                        return;
                        break;


                    case 'damaged':
                        statusText = 'Not Available';
                        statusColor = 'text-red-600';
                        isAvailable = true;
                        // SweetAlert message for rented items
                        // window.showError(
                        //     "This item is currently marked as Damaged, do you want to automatically change the status to Available and assign to this order?",
                        //     "Damaged "
                        // );
                        break;
                    case 'maintenance':
                        statusText = 'Maint. Hold';
                        statusColor = 'text-yellow-600';
                        isAvailable = true;
                        // SweetAlert message for rented items
                        // window.showError(
                        //     "This item is currently marked as Maint. Hold, do you want to automatically change the status to Available and assign to this order?",
                        //     "Maint. Hold "
                        // );
                        break;
                    default:
                        statusText = status || '';
                        statusColor = 'text-gray-600';
                        isAvailable = true;
                }

                statusDiv.textContent = `Status: ${statusText}`;
                statusDiv.className = `text-sm font-semibold ${statusColor}`;
                assignBtn.disabled = !isAvailable;
                pageLink.href = link;
                pageLink.textContent = title;
            }

            // Static elements inside the modal can use normal listeners
            if (equipmentSelect) {
                equipmentSelect.addEventListener('change', updateEquipmentStatus);
                // Initial status update
                updateEquipmentStatus();
            }

            // Form submit
            equipmentAssignForm?.addEventListener('submit', function(e) {
                e.preventDefault();

                if (window.$ && $(equipmentAssignForm).parsley && !$(equipmentAssignForm).parsley()
                    .isValid()) {
                    $(equipmentAssignForm).parsley().validate();
                    return;
                }

                const submitBtn = document.getElementById('equipment-assign-submit');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Assigning...';
                }

                const formData = new FormData(equipmentAssignForm);

                apiFetch('{{ route('admin.order-management.schedules.assign-equipment') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(data => {
                        if (data?.success) {
                            modal.classList.add('hidden');
                            if (window.notyf) notyf.success(data.message);
                            clearModalFields();
                            fetchEquipment();
                            fetchSchedules();
                        } else {
                            if (window.notyf) notyf.error(data?.message || 'Something went wrong.');
                        }
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Assign';
                        }
                    });
            });

            fetchEquipment();

            //  Fetch equipment options with cat
            function fetchEquipment(loadAll = true) {
                apiFetch('{{ route('admin.maintenance-management.equipment.fetch-with-categories') }}')
                    .then(data => {
                        if (data?.success) {

                            fullData = data.categories; // store full categories
                            // console.log(fullData);
                            categorySelect.innerHTML = '<option value="">Select Category</option>';

                            data.categories.forEach(cat => {
                                const option = document.createElement('option');
                                option.value = cat.id;
                                option.textContent = cat.title;
                                categorySelect.appendChild(option);
                            });

                            //  Load all equipment immediately after data arrives
                            loadAllEquipments();
                        }
                    });
            }


            function loadAllEquipments() {
                equipmentSelect.innerHTML = '<option value="">Select Equipment</option>';

                let equipments = [];

                fullData.forEach(cat => {
                    if (Array.isArray(cat.equipments)) {
                        equipments = equipments.concat(cat.equipments);
                    }
                });

                // Group by status
                const groups = {
                    available: [],
                    rented: [],
                    damaged: [],
                    maintenance: [],
                    other: []
                };

                equipments.forEach(equipment => {
                    const status = (equipment.current_status || '').toLowerCase();

                    if (status === 'available') groups.available.push(equipment);
                    else if (status === 'rented') groups.rented.push(equipment);
                    else if (status === 'damaged') groups.damaged.push(equipment);
                    else if (status === 'maintenance') groups.maintenance.push(equipment);
                    else groups.other.push(equipment);
                });



                appendGroup('Available', groups.available);
                appendGroup('Maint. Hold', groups.maintenance);
                appendGroup('Damaged', groups.damaged);
                appendGroup('Rented', groups.rented);
                appendGroup('Other', groups.other);


                updateEquipmentStatus();
            }


            function appendGroup(label, list) {
                if (list.length === 0) return;

                const group = document.createElement('optgroup');
                group.label = label;

                list.forEach(equipment => {
                    const opt = document.createElement('option');
                    opt.value = equipment.unique_id;
                    opt.textContent = equipment.equipment_name + " || " + equipment.equipment_id;
                    opt.setAttribute('data-current-status', equipment.current_status || '');
                    opt.setAttribute('data-link', equipment.link || '');
                    opt.setAttribute('data-link-title', equipment.link_title || '');
                    group.appendChild(opt);
                });

                equipmentSelect.appendChild(group);
            }
        });
    </script>
@endpush

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <!-- Left Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

            <!-- Left: Customer Info -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $customer->full_name }}</h2>
                <p class="text-sm text-gray-500 mt-1">Account: {{ $customer->unique_id }}</p>
                    <br>
                  <label class="text-sm text-gray-500 font-medium ">Company Name </label>
                <div class="static-view text-sm text-gray-900">{{ $customer->company_name ?? 'N/A' }}</div>

            </div>

            <!-- Right: Tax Status -->
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-1">Tax Status</p>
                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border border-green-200 bg-green-50 text-green-700">
                    @if ($customer->tax_status === 'Exempt')
                    <x-heroicon-o-shield-check class="w-5 h-5 text-green-500" />
                    @else
                    <x-heroicon-o-shield-check class="w-5 h-5 text-gray-500" />
                    @endif
                    {{ $customer->tax_status }}
                </div>
                <p class="text-xs text-gray-400 mt-1">Valid until {{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Right Card -->
<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">

    <!-- Assign to Funnels -->
    <div class="md:col-span-2 flex flex-col flex-grow">

        <div class="flex items-center justify-between mb-2">

            <label class="block text-sm font-medium text-gray-700">
                Assign to Funnels
            </label>

        </div>

        {{-- STATIC VIEW --}}
        <div class="static-view border border-gray-300 rounded-md p-5 bg-gray-50 min-h-[120px]">

            <div class="flex flex-wrap gap-2">

                @forelse($customer->funnels as $funnel)

                    <span
                        class="mb-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700 break-words whitespace-normal"
                    >

                        {{ $funnel->funnel_name }}

                    </span>

                @empty

                    <div class="w-full flex items-center justify-center text-gray-400 text-sm py-6">
                        No funnels assigned
                    </div>

                @endforelse

            </div>

        </div>

    </div>

</div>
</div>

<div class="mx-auto mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">

    <!-- Contact Information -->
    <div class="bg-white rounded-xl shadow-sm p-5">

    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-900 flex items-center gap-1">
            <svg class="w-5 h-5 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path>
                    </svg>
            Contact Information
        </h3>

        <!-- Edit Button -->
        <button
                type="button"
                onclick="OpenCustomerEditModal()"
                class="text-gray-500 hover:text-blue-600 transition">
            <svg class="w-5 h-5 " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"></path>
            </svg>
            </button>
        </div>

        <!-- TWO COLUMN GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700">

            <!-- COLUMN 1 -->
            {{-- <div class="space-y-4"> --}}
                <!-- Email -->
                <div class="md:col-span-2 flex items-center gap-2">

                    <svg class="w-4 h-4 text-gray-900 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-900">{{ $customer->email ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 font-medium">Email Address</p>
                    </div>
                </div>

                 <!-- Website -->
                <div class="md:col-span-2 flex items-center gap-2">
                    <x-heroicon-o-globe-alt class="w-4 h-4 text-blue-600 hover:text-blue-800 flex-shrink-0" />
                    <div>
                        <a href="{{ !empty($customer->company_website) ? $customer->company_website : 'jaavscript:void(0)' }}"
                            class="text-blue-600 hover:text-blue-800">


                        <p class="text-xs  font-medium">          {{ !empty($customer->company_website) ? $customer->company_website : 'N/A' }}</p>
                        </a>
                        <p class="text-xs text-gray-500 font-medium">Company Website</p>
                    </div>
                </div>

                <!-- Personal Phone -->
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-900 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-900">
                            {{ App\Helpers\CustomHelper::formatPhone($customer->phone) ?? 'N/A' }}
                        </p>
                        <p class="text-xs text-gray-500 font-medium">Personal Phone</p>
                    </div>
                </div>
            {{-- </div> --}}

            <!-- COLUMN 2 -->
            {{-- <div class="space-y-4"> --}}


                <!-- Company Phone -->
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-900 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-900">
                            {{ App\Helpers\CustomHelper::formatPhone($customer->company_phone) ?? 'N/A' }}
                        </p>
                        <p class="text-xs text-gray-500 font-medium">Company Phone</p>
                    </div>
                </div>
            {{-- </div> --}}

        </div>
    </div>


    <!-- Address Information -->
    <div class="bg-white rounded-xl shadow-sm p-5  overflow-y-scroll overflow-x-hidden">

        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-800 flex items-center gap-1">
                 <svg class="w-5 h-5 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"></path>
            </svg>
                Address Information
            </h3>

            <!-- Edit Button -->
            <button
                type="button"
                onclick="OpenCustomerEditModal()"
                class="text-gray-500 hover:text-blue-600 transition">
                 <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"></path>
            </svg>
            </button>
        </div>


        <div class="text-sm text-gray-700 space-y-4">

            @php
            $billingAddress = $customer->addresses->firstWhere('type', 'billing');
            $deliveryAddress = $customer->addresses->firstWhere('type', 'delivery');
            @endphp



            @foreach ($customer->addresses->where('is_primary', 1) as $addresse)
            <div>
                <p class="text-xs font-medium text-gray-500 mb-1">
                    {{ ($addresse->type=='Shipping' ? 'Delivery' : $addresse->type) }} Address

                </p>
                @php
                // Collect all non-empty fields except country
                $mainParts = array_filter([
                $addresse->address,
                $addresse->city,
                $addresse->state?->name,
                $addresse->zip_code,
                ]);

                // Add country only if there is at least one other part
                $parts = $mainParts;
                if (!empty($mainParts) && !empty($addresse->country)) {
                $parts[] = $addresse->country;
                }
                @endphp

                <p>{{ implode(', ', $parts) ?: 'No address provided.' }}</p>

            </div>
            <hr class="border-gray-200">
            @endforeach

        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col">
        <div class="flex items-center justify-between mb-2">
            <label for="ContactTags" class="block text-sm font-medium text-gray-700">Tags</label>

            <button type="button" onclick="openTagModal()" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add
                                </button>
        </div>
        <div class="flex flex-wrap gap-2 p-2 max-h-60 overflow-y-auto" id="customerTagsWrapper">
            <!-- show customer tags here (from JS, not from blade) -->
        </div>

    </div>

    <!-- Notes Section -->
    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col">
        <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium text-gray-700">Notes</label>

            <div class="flex items-center gap-3">
                <button type="button" id="viewAllNotesBtn"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    View All
                </button>

                <button type="button" id="addNoteBtn2"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add
                </button>
            </div>

        </div>

        <!-- Notes List -->
        <div id="noteListContainer" class="p-2 max-h-60 overflow-y-auto">
            <ul id="" class="list-disc text-sm text-gray-700 space-y-1 pl-3 ">
                @foreach ($customer->notes as $note)

                <li class="list-disc border-b border-gray-200 pb-3" data-id="5">
                    <div class="flex justify-between items-start">
                        <div class="flex-1 pr-3">
                            <p class="text-sm text-gray-800 leading-relaxed font-semibold">{{ $note->description }} </p>
                             <div class="mt-1 text-xs text-gray-500 space-y-1">

                {{-- Show ONLY "Created" if not updated --}}
                @if ($note->created_at->eq($note->updated_at))
                    <div>
                         {{ \App\Helpers\CustomHelper::formatDateTime($note->created_at) }}
                        by <span class="font-semibold">{{ $note->user->full_name }}</span>
                    </div>

                {{-- Show ONLY "Updated" if updated later --}}
                @else
                    <div>
                        {{ \App\Helpers\CustomHelper::formatDateTime($note->updated_at) }}
                        by <span class="font-semibold">{{ $note->user->full_name }}</span>
                    </div>
                @endif

            </div>
                        </div>
                    </div>
                </li>
                @endforeach

            </ul>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4  mx-auto mt-6">
    <!-- Current Balance -->
    <div class="bg-white p-4 rounded-xl shadow-sm flex items-center gap-4">
        <div class="bg-blue-100 text-blue-600 rounded-md p-2">
            <!-- Dollar Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6">
                <line x1="12" x2="12" y1="2" y2="22"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Current Balance</p>
            <p class="text-xl font-semibold text-gray-900">

                {{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance ) }}


                <!-- {{ config('app.currency.code') }}{{ $customer->available_credit_balance ?? 0 }} -->

            </p>
        </div>
    </div>

    <!-- Available Credit -->
    <div class="bg-white p-4 rounded-xl shadow-sm flex items-center gap-4">
        <div class="bg-green-100 text-green-600 rounded-md p-2">
            <!-- Trending Up Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-6 h-6">
                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                <polyline points="16 7 22 7 22 13"></polyline>
            </svg>
        </div>
        <div>
            <p class="text-sm  text-gray-500">Available Credit</p>
            <p class="text-xl font-semibold text-gray-900">

                <!-- {{ config('app.currency.code') }}{{ number_format(($customer->credit_limit ?? 0) - ($customer->total_account_order_amount ?? 0), 2) }}  -->

                {{ \App\Helpers\CustomHelper::formatCurrency(\App\Helpers\CustomHelper::getAvailableCredit($customer)) }}


            </p>
        </div>
    </div>

    <!-- Open Invoices -->
    <div class="bg-white p-4 rounded-xl shadow-sm flex items-center gap-4">
        <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
            <!-- Document Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-6 h-6">
                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                <path d="M10 9H8"></path>
                <path d="M16 13H8"></path>
                <path d="M16 17H8"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm  text-gray-500">Open Invoices</p>
            <p class="text-xl font-semibold text-gray-900">{{ $customer->unpaid_invoices_count  }}</p>
        </div>
    </div>

    <!-- Last Payment -->
    <div class="bg-white p-4 rounded-xl shadow-sm flex items-center gap-4">
        <div class="bg-green-100 text-green-600 rounded-md p-2">
            <!-- Calendar Icon -->
            <x-heroicon-o-calendar class="w-6 h-6 text-green-500" />
        </div>
        <div>
            <p class="text-sm  text-gray-500">Last Payment</p>
            <p class="text-xl font-semibold text-gray-900">{{ App\Helpers\CustomHelper::formatDate($lastpaymentdate) ?? 'N/A' }} </p>
        </div>
    </div>
</div>

<div class=" mx-auto  bg-white rounded-2xl shadow-sm mt-6">
    <div class="flex items-center p-4 justify-between border-b border-gray-200">
        <h2 class="text-base font-semibold text-gray-900">Recent Orders</h2>
        <a href="#" class="text-sm text-blue-600" id="viewAllOrdersLink">View All</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
            <thead class="border-b bg-gray-50 font-semibold text-gray-700 border-gray-200">
                <tr>
                    <th class="py-4 px-6">Order ID</th>
                    <th class="py-4 px-6">PO#</th>

                    <th class="py-4 px-6">Product Name</th>
                    <th class="py-4 px-6 w-32 ">Amount</th>
                    <th class="py-4 px-6 w-32  text-right">Payment Method</th>
                    <th class="py-4 px-6 w-32 text-right">Status</th>
                    <th class="py-4 px-6 w-32 text-right">Created</th>
                    <th class="py-4 px-6 w-24 text-end">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @php $rowCount = 0; @endphp

                @forelse ($customer->orders->sortByDesc('order_date') as $order)
                @foreach ($order->products as $product)

                @if ($rowCount >= 5)
                @break(2)
                @endif

                @php $rowCount++; @endphp

                <tr class="hover:bg-gray-50" data-status="{{ strtolower($order->status) }}">
                    {{-- Order Number --}}
                    <td class="py-4 px-6 font-medium text-gray-900">
                        {!! $order->view_link !!}
                    </td>
                     {{-- Order Number --}}
                    <td class="py-4 px-6 font-medium text-gray-900">
                        {{ $order->po_id }}
                    </td>

                    {{-- Product Name --}}
                    <td class="py-4 px-6 truncate min-w-3xs max-w-3xs ">
                        {{ $product->product_name ?? 'N/A' }}
                    </td>

                    {{-- Total (for this product) --}}
                    <td class="py-4 px-6">
                        <!-- {{ config('app.currency.code') }}{{ number_format($product->total, 2) }} -->

                        {{ \App\Helpers\CustomHelper::formatCurrency($product->total ) }}


                    </td>

                    {{-- Payment Type --}}
                    <td class="py-4 px-6 text-right">
                        {{ $order?->last_payment_type?->label() ?? '-' }}
                    </td>

                    {{-- Status Badge --}}
                    <td class="py-4 px-6 text-right">


                        {!! \App\Helpers\CustomHelper::statusBadge($order?->last_payment_status) !!}


                    </td>

                    {{-- Order Date --}}
                    <td class="py-4 px-6 text-right">
                        {{ App\Helpers\CustomHelper::formatDate($order->order_date) ?? 'N/A' }}
                    </td>

                    {{-- Actions --}}
                    <td class="py-4 px-6 ">
                        <div class="flex gap-2 items-center justify-end">
                            <a href="{{ route('admin.order-management.orders.edit', $order->unique_id) }}"  title="View">
                                <x-heroicon-o-eye class="w-5 h-5 text-blue-600" />
                            </a>
                            <a href="{{ route('admin.order-management.orders.receipt-download', $order->unique_id) }}" title="Download">
                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600" />
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-4 text-center text-gray-500">
                        No orders found.
                    </td>
                </tr>
                @endforelse


            </tbody>
        </table>
    </div>
</div>



<!-- Notes Modal -->
<div id="notesModal2" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-sm flex flex-col max-h-full overflow-hidden border border-gray-200">

            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900" id="noteModalTitle2">Add Note</h2>
                <button type="button" onclick="closeNotesModal2()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto max-h-[70vh] mt-5 mb-5">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">User</label>

                    {!! html()->select(
                    'user_id',
                    $employees->pluck('full_name', 'id')->toArray()
                    )->id('user_id')->class([
                    'w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                    ]) !!}

                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Note</label>
                    <textarea id="note_text2" rows="5"
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500"
                        placeholder="Enter note..."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 pb-4 px-4 border-t border-gray-200">
                <button type="button" onclick="closeNotesModal2()" class="px-6 py-3  text-md rounded border border-gray-300 bg-white">Close</button>
                <button type="button" id="saveNoteBtn2" class="px-6 py-3  text-md rounded border border-grey-300 bg-blue-600 text-white">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Notes History Modal --}}
<div id="notesHistoryModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="w-full max-w-4xl mx-auto bg-white rounded-lg shadow-xl flex flex-col max-h-[90vh] border border-gray-200">

        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Notes History</h2>
            <button type="button" id="closeNotesHistoryBtn" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>

        <div class="px-4 py-3 border-b border-gray-100">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                <input type="text" id="notesHistorySearch" placeholder="Search notes by keyword..."
                    class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-md text-sm focus:ring focus:border-blue-500" />
            </div>
        </div>

        <div id="notesHistoryList" class="overflow-y-auto flex-1 p-4 space-y-3">
        </div>

    </div>
</div>

@push('js')

<!-- this is for appear tags her form choice js  -->

<script>
    function openNotesModal() {
        document.getElementById('notesModal2').classList.remove('hidden');

    }

    function closeNotesModal2() {
        document.getElementById('notesModal2').classList.add('hidden');
    }
</script>

<!-- notes  -->
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const notesModal2 = document.getElementById('notesModal2');
        const noteText = document.getElementById('note_text2');
        const userSelect = document.getElementById('user_id');
        const noteList = document.querySelector('#noteListContainer ul');


        const addNoteBtn2 = document.getElementById('addNoteBtn2');
        const saveNoteBtn2 = document.getElementById('saveNoteBtn2');
        const modalTitle = document.getElementById('noteModalTitle2');

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        //  Open Add Note modal
        addNoteBtn2.addEventListener('click', () => {
            modalTitle.textContent = "Add Note";
            noteText.value = "";
            userSelect.selectedIndex = 0;
            editNoteId = null;
            notesModal2.classList.remove('hidden');
        });

        //  Close modal
        window.closeNotesModal2 = function() {
            notesModal2.classList.add('hidden');
        };

         // Fetch all notes (from backend API)
        window.fetchNotes2 = function() {
        return fetch(`{{ route('admin.crm.customers.notes.fetch', $customer->id ?? 0) }}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        notes = data.notes || [];
                        renderNotes();
                    } else {
                        notyf.error("Failed to fetch notes");
                    }
                })
                .catch(() => notyf.error("Error fetching notes"));
        }


       // Render Notes List
        function renderNotes() {
            if (notes.length === 0) {
                noteList.innerHTML = `<li class="text-gray-400 text-sm">No notes available.</li>`;
                return;
            }

            noteList.innerHTML = notes
                .map(note => {
                    const isUpdated = note.updated_at && note.updated_at !== note.created_at;

                    const noteText = note.text
                    .replace(/\n/g, '<br>')
                    .replace(/Reason:/g, '<strong>Reason:</strong>')
                    .replace(/Priority:/g, '<strong>Priority:</strong>')
                    .replace(/Notes:/g, '<strong>Notes:</strong>');

                    return `
                        <li class="list-disc border-b border-gray-200 pb-3" data-id="${note.id}">
                            <div class="flex justify-between items-start">
                                <div class="flex-1 pr-3">
                                    <p class="text-sm text-gray-800 leading-relaxed">
                                        ${noteText}
                                    </p>
                                    <div class="mt-1 text-xs text-gray-500 space-y-1">

                                        ${!isUpdated
                                            ? `
                                                <div>
                                                    ${note.created_at}
                                                    by <span class="font-semibold">${note.user_name}</span>
                                                </div>
                                            `
                                            : `
                                                <div>
                                                    ${note.updated_at}
                                                    by <span class="font-semibold">${note.user_name}</span>
                                                </div>
                                            `
                                        }

                                    </div>
                                </div>

                            </div>
                        </li>
                    `;
                })
                .join('');
        }


        //  Save note (create or update)
        saveNoteBtn2.addEventListener('click', () => {
            const text = noteText.value.trim();
            const userId = userSelect.value;

            if (!text || !userId) {
                notyf.error('Please fill all fields.');
                return;
            }

            const payload = {
                text,
                user_id: userId
            };

                fetch(`{{ route('admin.crm.customers.notes.store', $customer->id ?? 0) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            notyf.success('Note added successfully');

                            closeNotesModal2();

            //  fetchNotes2();
            reloadGlobalNotes();
                        } else {
                            notyf.error(data.message || 'Failed to add note');
                        }
                    })
                    .catch(() => notyf.error('Error adding note'));

        });


        // ── View All Notes History Modal ──────────────────────────────────
        const viewAllNotesBtn    = document.getElementById('viewAllNotesBtn');
        const notesHistoryModal  = document.getElementById('notesHistoryModal');
        const notesHistorySearch = document.getElementById('notesHistorySearch');
        const notesHistoryList   = document.getElementById('notesHistoryList');
        const closeHistoryBtn    = document.getElementById('closeNotesHistoryBtn');

        function renderHistoryNotes(list) {
            if (!list.length) {
                notesHistoryList.innerHTML = '<p class="text-gray-400 text-sm text-center py-10">No notes found.</p>';
                return;
            }
            notesHistoryList.innerHTML = list.map(note => {
                const isUpdated = note.updated_at && note.updated_at !== note.created_at;
                const formatted = note.text
                    .replace(/\n/g, '<br>')
                    .replace(/Reason:/g, '<strong>Reason:</strong>')
                    .replace(/Priority:/g, '<strong>Priority:</strong>')
                    .replace(/Notes:/g, '<strong>Notes:</strong>');
                return `
                    <div class="border border-gray-200 rounded-lg p-4 bg-white hover:shadow-sm transition">
                        <p class="text-sm text-gray-800 leading-relaxed">${formatted}</p>
                        <div class="mt-2 text-xs text-gray-500">
                            ${isUpdated ? note.updated_at : note.created_at}
                            by <span class="font-semibold">${note.user_name}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        if (viewAllNotesBtn) {
            viewAllNotesBtn.addEventListener('click', () => {
                notesHistorySearch.value = '';
                renderHistoryNotes(notes);
                notesHistoryModal.classList.remove('hidden');
            });
        }

        if (closeHistoryBtn) {
            closeHistoryBtn.addEventListener('click', () => notesHistoryModal.classList.add('hidden'));
        }

        notesHistoryModal.addEventListener('click', e => {
            if (e.target === notesHistoryModal) notesHistoryModal.classList.add('hidden');
        });

        notesHistorySearch.addEventListener('input', () => {
            const q = notesHistorySearch.value.toLowerCase().trim();
            const filtered = q ? notes.filter(n => n.text.toLowerCase().includes(q)) : notes;
            renderHistoryNotes(filtered);
        });

    });
</script>


@endpush

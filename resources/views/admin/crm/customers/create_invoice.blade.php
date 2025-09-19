@extends('admin.layouts.app')

@section('title', 'invoice create')

@push('css')
@endpush

@section('content')

@include('flash::message')


<div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <a href="{{ route('admin.crm.customers.invoice.index',$customer->unique_id ) }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path>
                </svg>
                <span class="text-sm font-medium">Back to Invoice</span>
            </a>
            <div class="hidden sm:block h-6 border-l border-gray-300"></div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Invoice</h1>
                <p class="text-sm text-gray-500">Invoice <span id="invoiceNumberDisplay">#INV-2025-920 </span> for {{ $customer->company_name ?? ' ' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="cancel" name="action" value="close" class="inline-flex items-center px-6 py-2 rounded-md text-gray-700 bg-white text-sm font-medium shadow transition"> Cancel
            </button>

            <button type="submit" name="action" value="save_new" disabled="" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-sm font-medium shadow transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save w-4 h-4 mr-2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Create Invoice
            </button>
        </div>
    </div>
</div>

<div class="min-h-screen bg-gray-50 mt-6">
    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left side -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Invoice Details -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="flex items-center gap-2 text-lg font-semibold mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-5 h-5">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                        <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                        <path d="M10 9H8"></path>
                        <path d="M16 13H8"></path>
                        <path d="M16 17H8"></path>
                    </svg>
                    Invoice Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number</label>
                        <input id="invoiceNumberInput" type="text" class=" w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="INV-2025-153">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Date</label>
                        <input class="w-full border rounded-md datepicker px-3 py-2 text-sm bg-white text-gray-700 border-gray-300" type="text" name="invoice_date" id="invoice_date" placeholder="MM-DD-YYYY" autocomplete="off">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <input class="w-full border rounded-md datepicker px-3 py-2 text-sm bg-white text-gray-700 border-gray-300" type="text" name="due_date" id="due_date" placeholder="MM-DD-YYYY" autocomplete="off">
                    </div>
                </div>
            </div>

            <!-- Add Items -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-semibold mb-4">Add Items to Invoice</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                    <a href="javascript:void(0)" id="openChargeModal" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-4 h-4">
                            <path d="M5 12h14"></path>
                            <path d="M12 5v14"></path>
                        </svg>
                        Charge
                    </a>
                    <a href="javascript:void(0)" id="openDiscountModal" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-4 h-4">
                            <circle cx="12" cy="8" r="6"></circle>
                            <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                        </svg>
                        Discount
                    </a>
                    <a href="javascript:void(0)" id="openRefundModal" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                        Refund
                    </a>
                    <a href="javascript:void(0)" id="openOrderModal" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart w-4 h-4">
                            <circle cx="8" cy="21" r="1"></circle>
                            <circle cx="19" cy="21" r="1"></circle>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                        </svg>
                        From Order
                    </a>
                </div>
                <!-- EMPTY STATE (when no items) -->
                <div id="emptyState" class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package w-12 h-12 text-gray-400 mx-auto mb-4">
                        <path d="m7.5 4.27 9 5.15"></path>
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                        <path d="m3.3 7 8.7 5 8.7-5"></path>
                        <path d="M12 22V12"></path>
                    </svg>
                    <p class="text-sm">No items added to this invoice yet.</p>
                    <p class="text-sm text-gray-400 mt-1">Use the buttons above to add charges, discounts, refunds, or items from existing orders.</p>
                </div>
                <!-- TABLE STATE (hidden by default until items exist) -->
                <div id="itemsTable" class="overflow-x-auto hidden rounded-lg shadow border border-gray-200">
                    <table class="min-w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Description</th>
                                <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700 whitespace-nowrap">Qty</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-700 whitespace-nowrap">Unit Price</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-700 whitespace-nowrap">Total</th>
                                <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700 whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceItems" class="divide-y divide-gray-200">
                            <!-- rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoice Notes -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-semibold mb-4">Invoice Notes</h3>
                <textarea rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter any additional notes for this invoice..."></textarea>
            </div>
        </div>

        <!-- Right side -->
        <div class="space-y-6">
            <!-- Bill To -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="flex items-center gap-2 text-lg font-semibold mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-5 h-5 mr-2">
                        <rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect>
                        <path d="M9 22v-4h6v4"></path>
                        <path d="M8 6h.01"></path>
                        <path d="M16 6h.01"></path>
                        <path d="M12 6h.01"></path>
                        <path d="M12 10h.01"></path>
                        <path d="M12 14h.01"></path>
                        <path d="M16 10h.01"></path>
                        <path d="M16 14h.01"></path>
                        <path d="M8 10h.01"></path>
                        <path d="M8 14h.01"></path>
                    </svg>
                    Bill To
                </h3>
                <p class="font-semibold">{{ $customer->company_name ?? 'N/A' }}</p>
                <p class="text-gray-600">{{ $customer->full_name ?? 'N/A' }}</p>

                <div class="flex items-center text-sm text-gray-600 gap-2 mt-2">
                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                    {{ $customer->email ?? 'N/A' }}
                </div>
                <div class="flex items-center text-sm text-gray-600 gap-2 mt-2">
                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                    {{ App\Helpers\CustomHelper::formatPhone($customer->phone) }}
                </div>

                @php
                $billingAddress = $customer->addresses->where('is_primary', 1)->firstWhere(fn ($a) => $a->type === 'Billing' );
                $shippingAddress = $customer->addresses->where('is_primary', 1)->firstWhere(fn ($a) => $a->type === 'Shipping');

                $defaultAddresses = [
                ['label' => 'Billing', 'data' => $billingAddress],
                ['label' => 'Shipping', 'data' => $shippingAddress],
                ];
                @endphp

                @foreach ($defaultAddresses as $index => $addressItem)
                @php $addresse = $addressItem['data']; @endphp

                <div class="flex items-center text-sm text-gray-600 gap-2 mt-2">
                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                    @if($addresse?->full_name)
                    {{ $addresse->full_name }}
                    @endif

                    @if($addresse?->address || $addresse?->city)
                    {{ $addresse->address ?? '' }}{{ $addresse?->city ? ', ' . $addresse->city : '' }}
                    @endif

                    @if($addresse?->state?->name || $addresse?->zip_code)
                    {{ $addresse?->state?->name ?? '' }}{{ $addresse?->zip_code ? ' ' . $addresse->zip_code : '' }}
                    @endif
                    - {{ ($addressItem['label']=='Shipping' ? 'Delivery' : $addressItem['label']) }} Address
                </div>
                @endforeach


                <div class="flex items-center mt-3">

                    @if ($customer->tax_status === 'Exempt')
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check w-4 h-4 mr-2 text-green-600">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                        <path d="m9 12 2 2 4-4"></path>
                    </svg>
                    <span class="text-sm font-medium text-green-600">{{ $customer->tax_status ?? 'N/A' }}</span>
                    @else
                    <x-heroicon-o-shield-check class="w-5 h-5 text-gray-500" />
                    <span class="text-sm font-medium text-red-600">{{ $customer->tax_status ?? 'N/A' }}</span>
                    @endif

                </div>

            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Invoice Summary</h3>
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-gray-700">Subtotal:</span>
                    <span class="font-medium text-gray-900">$40.61</span>
                </div>

                <!-- Sales Tax -->
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-gray-700">Sales Tax:</span>
                    <span class="text-green-600 font-medium">Tax Exempt</span>
                </div>

                <!-- Divider -->
                <hr class="my-2 border-gray-300">

                <!-- Total -->
                <div class="flex justify-between pt-2 font-bold text-lg">
                    <span>Total:</span>
                    <span class="text-gray-900">$40.61</span>
                </div>
            </div>

        </div>
    </div>
</div>



<!-- New Charge Wrapper -->
<div id="chargeModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-red-600 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-plus w-5 h-5">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">New Charge</h2>
                </div>
                <button id="closeChargeModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto">
                <form class="space-y-8" id="chargeForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input id="amount" type="text" placeholder="0.00" maxlength="8" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm">
                        </div>

                        <div id="clueBox" class="hidden mt-3 bg-blue-50 border border-blue-200 rounded-md p-4 text-sm space-y-1">
                            <p class="font-semibold text-gray-700">Calculation Preview</p>
                            <div class="flex justify-between">
                                <span class="text-blue-700">Amount:</span>
                                <span id="amountPreview" class="text-blue-900">$0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-700">Sales Tax:</span>
                                <span id="taxPreview" class="text-blue-900">$0.00</span>
                            </div>
                            <div class="flex justify-between font-semibold border-t border-blue-300 pt-1">
                                <span class="text-blue-700">Total Balance Change:</span>
                                <span class="text-blue-900" id="totalPreview">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <!-- Charge Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Reason </label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" name="reason" id="reason" required="">
                            <option value="">Select charge reason</option>
                            <option value="New Rental">New Rental</option>
                            <option value="Rental Extension">Rental Extension</option>
                            <option value="Damages">Damages</option>
                            <option value="Fuel Charge">Fuel Charge</option>
                            <option value="Cleaning Charge">Cleaning Charge</option>
                            <option value="Missing Items">Missing Items</option>
                            <option value="Product Purchase">Product Purchase</option>
                        </select>
                    </div>

                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible </label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="responsible_person" id="responsible_person" required="">
                            <option value="">Select person responsible</option>
                            <option value="1">Raj Chotaliya</option>
                            <option value="2">Jigar Khatri</option>
                            <option value="3">Nipa Soni</option>
                            <option value="4">Gary Jezorski</option>
                            <option value="5">Akshay Vyas</option>
                            <option value="9">Jigar khatri</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference (Optional)</label>
                        <div class="relative">
                            <input class="px-3 py-2 w-full border border-gray-300 rounded-md text-sm" type="text" placeholder="Enter reference number or ID...">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="notes" id="notes" rows="3" placeholder="Describe the reason for this charge..."></textarea>
                    </div>

                    <div class="flex  gap-2 pb-4">
                        <button type="button" id="cancelChargeBtn" class="px-4 py-2 flex-1 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitChargeBtn" class="relative flex-1 px-4 py-2 text-sm rounded bg-blue-600 text-white flex items-center justify-center gap-2">
                            <span id="chargeBtnText">Add Charge</span>
                            <svg id="chargeBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- New Discount Wrapper -->
<div id="discountModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-purple-600 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-5 h-5">
                            <circle cx="12" cy="8" r="6"></circle>
                            <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Apply Discount</h2>
                </div>
                <button id="closeDiscountModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto">
                <form class="space-y-8">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Amount </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input id="damount" type="text" placeholder="0.00" maxlength="8" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm">
                        </div>


                    </div>
                    <!-- Discount Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Reason </label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="reason" id="reason" required="">
                            <option value="">Select discount reason</option>
                            <option value="Volume Discount">Volume Discount</option>
                            <option value="Repeat Customer Discount">Repeat Customer Discount</option>
                            <option value="Damage Waiver Protection">Damage Waiver Protection</option>
                            <option value="Misc. Management Discount">Misc. Management Discount</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible </label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="responsible_person" id="responsible_person" required="">
                            <option value="">Select person responsible</option>
                            <option value="1">Raj Chotaliya</option>
                            <option value="2">Jigar Khatri</option>
                            <option value="3">Nipa Soni</option>
                            <option value="4">Gary Jezorski</option>
                            <option value="5">Akshay Vyas</option>
                            <option value="9">Jigar khatri</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference (Optional)</label>
                        <div class="relative">
                            <input class="px-3 py-2 w-full border border-gray-300 rounded-md text-sm" type="text" placeholder="Enter reference number or ID...">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="notes" id="notes" rows="3" placeholder="Describe the reason for this discount..."></textarea>
                    </div>

                    <div class="flex  gap-2 pb-4">
                        <button type="button" id="cancelDiscountBtn" class="px-4 py-2 flex-1 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitDiscountBtn" class="relative flex-1 px-4 py-2 text-sm rounded bg-blue-600 text-white flex items-center justify-center gap-2">
                            <span id="discountBtnText">Add Discount</span>
                            <svg id="discountBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- New Refund Wrapper -->
<div id="refundModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-purple-600 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-5 h-5 mr-2 text-blue-600">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Process Refund</h2>
                </div>
                <button id="closeRefundModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto">
                <form class="space-y-8">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Refund Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input id="ramount" type="text" placeholder="0.00" maxlength="8" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm">
                        </div>


                    </div>
                    <!-- Refund Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Refund Reason</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="reason" id="reason" required="">
                            <option value="">Select refund reason</option>
                            <option value="Damaged Item">Damaged Item</option>
                            <option value="Wrong Item Shipped">Wrong Item Shipped</option>
                            <option value="Customer Cancellation">Customer Cancellation</option>
                            <option value="Billing Overcharge">Billing Overcharge</option>
                            <option value="Duplicate Charge">Duplicate Charge</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="responsible_person" id="responsible_person" required="">
                            <option value="">Select person responsible</option>
                            <option value="1">Raj Chotaliya</option>
                            <option value="2">Jigar Khatri</option>
                            <option value="3">Nipa Soni</option>
                            <option value="4">Gary Jezorski</option>
                            <option value="5">Akshay Vyas</option>
                            <option value="9">Jigar khatri</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference (Optional)</label>
                        <div class="relative">
                            <input class="px-3 py-2 w-full border border-gray-300 rounded-md text-sm" type="text" placeholder="Enter reference number or ID...">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="notes" id="notes" rows="3" placeholder="Describe the reason for this refund..."></textarea>
                    </div>

                    <div class="flex  gap-2 pb-4">
                        <button type="button" id="cancelRefundBtn" class="px-4 py-2 flex-1 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitRefundBtn" class="relative flex-1 px-4 py-2 text-sm rounded bg-blue-600 text-white flex items-center justify-center gap-2">
                            <span id="refundBtnText">Add Discount</span>
                            <svg id="refundBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- From Order Wrapper -->
<div id="orderModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-6xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-purple-600 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart w-5 h-5 mr-2 text-blue-600">
                            <circle cx="8" cy="21" r="1"></circle>
                            <circle cx="19" cy="21" r="1"></circle>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Select Order to Add to Invoice</h2>
                </div>
                <button id="closeOrderModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="px-6 pb-6">
                <div class="overflow-x-auto rounded-lg shadow border border-gray-200">
                    <table class="w-full text-left">
                        <thead class="bg-gray-100 text-sm font-semibold text-gray-700">
                            <tr>
                                <th class="px-4 py-3 whitespace-nowrap">Order ID</th>
                                <th class="px-4 py-3 whitespace-nowrap">Date</th>
                                <th class="px-4 py-3 whitespace-nowrap">Primary Product</th>
                                <th class="px-4 py-3 whitespace-nowrap">Total</th>
                                <th class="px-4 py-3 whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y text-sm ">
                            <tr>
                                <td class="px-4 py-3 font-medium whitespace-nowrap">ORD-2025-001</td>
                                <td class="px-4 py-3 whitespace-nowrap">Jan 15, 2025</td>
                                <td class="px-4 py-3 whitespace-nowrap">Premium Widget Set</td>
                                <td class="px-4 py-3 font-semibold whitespace-nowrap">$1,249.95</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                        Delivered
                                    </span>
                                </td>
                                <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                                    <a class="text-blue-600 gap-2 inline-flex items-center justify-center" href="javascript:void(0)" id="openviewdetailsModal"> <x-heroicon-o-eye class="w-4 h-4" /> View Details</a>
                                    <button class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 add-to-invoice-btn">
                                        + Add to Invoice
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium whitespace-nowrap">ORD-2025-002</td>
                                <td class="px-4 py-3 whitespace-nowrap">Jan 20, 2025</td>
                                <td class="px-4 py-3 whitespace-nowrap">Standard Widget Pack</td>
                                <td class="px-4 py-3 font-semibold whitespace-nowrap">$875.50</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                        Shipped
                                    </span>
                                </td>
                                <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                                    <button class="text-blue-600 gap-2 inline-flex items-center justify-center"> <x-heroicon-o-eye class="w-4 h-4" /> View Details</button>
                                    <button class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
                                        + Add to Invoice
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium whitespace-nowrap">ORD-2025-003</td>
                                <td class="px-4 py-3 whitespace-nowrap">Jan 25, 2025</td>
                                <td class="px-4 py-3 whitespace-nowrap">Widget Accessories Kit</td>
                                <td class="px-4 py-3 font-semibold whitespace-nowrap">$624.50</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">
                                        Processing
                                    </span>
                                </td>
                                <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                                    <button class="text-blue-600 gap-2 inline-flex items-center justify-center"> <x-heroicon-o-eye class="w-4 h-4" /> View Details</button>
                                    <button class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
                                        + Add to Invoice
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Details Wrapper -->
<div id="viewdetailsModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-4xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Order Details - ORD-2025-001</h2>
                </div>
                <button id="closeViewdetailsModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="p-6 space-y-6 overflow-y-auto">

                <!-- Order Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Order Date</p>
                        <p class="font-medium">Jan 15, 2025</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Order Total</p>
                        <p class="font-semibold text-lg">$1,249.95</p>
                    </div>
                </div>

                <!-- Add all products -->
                <div class="flex flex-col sm:flex-row sm:justify-between items-center p-4 border rounded-lg bg-blue-50">
                    <div>
                        <p class="text-sm font-medium text-blue-900">Add All Products from Order</p>
                        <p class="text-xs text-blue-700 mt-1 mb-2">This will add each product as individual line items. Total: $1,249.95</p>
                    </div>
                    <button class="bg-green-600 text-white px-6 py-2 rounded-md add-to-invoice-btn text-sm">
                        + Add to Invoice
                    </button>
                </div>

                <!-- Product Items -->
                <div class="space-y-4">
                    <!-- Product 1 -->
                    <div class="flex justify-between items-start p-4 border rounded-lg whitespace-nowrap">
                        <div>
                            <p class="font-medium">Premium Widget Set</p>
                            <p class="text-sm text-gray-600">SKU: PWS-001</p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="inline-block bg-gray-100 text-xs text-gray-600 px-2 py-1 rounded">Color: Blue</span>
                                <span class="inline-block bg-gray-100 text-xs text-gray-600 px-2 py-1 rounded">Size: Large</span>
                                <span class="inline-block bg-gray-100 text-xs text-gray-600 px-2 py-1 rounded">Material: Aluminum</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">$999.95</p>
                            <p class="text-sm text-gray-500">5 × $199.99</p>
                        </div>
                    </div>

                    <!-- Product 2 -->
                    <div class="flex justify-between items-start p-4 border rounded-lg whitespace-nowrap">
                        <div>
                            <p class="font-medium">Installation Service</p>
                            <p class="text-sm text-gray-600">SKU: INST-001</p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="inline-block bg-gray-100 text-xs text-gray-600 px-2 py-1 rounded">Service Type: On-site Installation</span>
                                <span class="inline-block bg-gray-100 text-xs text-gray-600 px-2 py-1 rounded">Technician Level: Senior</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">$250.00</p>
                            <p class="text-sm text-gray-500">1 × $250.00</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>



@endsection

@push('js')

<script>
    const invoiceInput = document.getElementById("invoiceNumberInput");
    const invoiceDisplay = document.getElementById("invoiceNumberDisplay");

    invoiceInput.addEventListener("input", function() {
        const value = this.value.trim();
        invoiceDisplay.textContent = value ? `#${value}` : "#INV-2025-920";
    });
</script>

<script>
    window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('chargeModalWrapper');
        const openBtn = document.getElementById('openChargeModal');
        const closeBtn = document.getElementById('closeChargeModalBtn');
        const cancelBtn = document.getElementById('cancelChargeBtn');
        const form = document.getElementById('chargeForm');

        const emptyState = document.getElementById("emptyState");
        const itemsTable = document.getElementById("itemsTable");
        const invoiceItems = document.getElementById("invoiceItems");

        const amountInput = document.getElementById("amount");
        const clueBox = document.getElementById("clueBox");
        const amountPreview = document.getElementById("amountPreview");
        const taxPreview = document.getElementById("taxPreview");
        const totalPreview = document.getElementById("totalPreview");

        // open/close modal
        openBtn.addEventListener('click', () => modalWrapper.style.display = 'flex');
        const closeModal = () => {
            modalWrapper.style.display = 'none';
            form.reset();
            clueBox.classList.add("hidden");
        };
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // preview calculation
        amountInput.addEventListener("input", function() {
            this.value = this.value.replace(/[^0-9.]/g, "");
            const amount = parseFloat(this.value) || 0;
            const taxRate = 0.098;
            const tax = amount * taxRate;
            const total = amount + tax;
            amountPreview.textContent = `$${amount.toFixed(2)}`;
            taxPreview.textContent = `$${tax.toFixed(2)}`;
            totalPreview.textContent = `$${total.toFixed(2)}`;
            clueBox.classList.toggle("hidden", this.value.trim() === "");
        });

        // handle submit
        form.addEventListener("submit", e => {
            e.preventDefault();
            const desc = document.getElementById("reason").value || "New Charge";
            const notes = document.getElementById("notes").value.trim();
            const price = parseFloat(amountInput.value) || 0;
            const qty = 1;
            const taxRate = 0.098;
            const total = price + (price * taxRate);

            if (price <= 0) {
                alert("Enter valid amount");
                return;
            }

            const row = document.createElement("tr");
            row.innerHTML = `
            <td class="px-4 py-3 whitespace-nowrap">
                <div class="font-medium text-gray-900">${desc}</div>
                ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
            </td>
            <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${qty}</td>
            <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${price.toFixed(2)}</td>
            <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap">$${total.toFixed(2)}</td>
            <td class="px-4 py-3 text-center h-full items-center justify-center gap-3 whitespace-nowrap">
                <button type="button" class="text-blue-600 edit-btn mr-2"><x-heroicon-o-pencil class="w-4 h-4" /></button>
                <button type="button" class="text-red-600 delete-btn"><x-heroicon-o-trash class="w-4 h-4" /></button>
            </td>
        `;

            row.querySelector(".delete-btn").addEventListener("click", () => {
                row.remove();
                if (!invoiceItems.children.length) {
                    itemsTable.classList.add("hidden");
                    emptyState.classList.remove("hidden");
                }
            });

            invoiceItems.appendChild(row);
            emptyState.classList.add("hidden");
            itemsTable.classList.remove("hidden");

            closeModal();
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const discountModal = document.getElementById("discountModalWrapper");
        const discountForm = discountModal.querySelector("form");
        const itemsTableWrap = document.getElementById("itemsTable");
        const invoiceItemsTBody = document.getElementById("invoiceItems");
        const emptyState = document.getElementById("emptyState"); // if present

        const openBtn = document.getElementById("openDiscountModal");
        const closeBtn = document.getElementById("closeDiscountModalBtn");
        const cancelBtn = document.getElementById("cancelDiscountBtn");

        const TAX_RATE = 0.098; // 9.8%

        // ---- Modal Open/Close ----
        openBtn.addEventListener("click", () => {
            discountModal.style.display = "flex";
        });

        const closeModal = () => {
            discountModal.style.display = "none";
        };

        closeBtn.addEventListener("click", closeModal);
        cancelBtn.addEventListener("click", closeModal);

        // Close modal if clicking outside
        discountModal.addEventListener("click", (e) => {
            if (e.target === discountModal) closeModal();
        });

        // ---- Add Discount Row ----
        discountForm.addEventListener("submit", function(e) {
            e.preventDefault();

            const amountEl = discountModal.querySelector("#damount");
            const reasonEl = discountModal.querySelector("#reason");
            const notesEl = discountModal.querySelector("#notes");

            const rawAmount = (amountEl.value || "").trim();
            const reason = (reasonEl.value || "").trim();
            const notes = (notesEl.value || "").trim();

            if (!rawAmount || !reason) {
                alert("Please enter a discount amount and select a reason.");
                return;
            }

            const amount = parseFloat(rawAmount.replace(/[^0-9.]/g, "")) || 0;
            const qty = 1;
            const total = amount + (amount * TAX_RATE); // adjust if needed

            const tr = document.createElement("tr");
            tr.innerHTML = `
      <td class="px-4 py-3 whitespace-nowrap">
        <div class="font-medium text-gray-900">${reason}</div>
        ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
      </td>
      <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${qty}</td>
      <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${amount.toFixed(2)}</td>
      <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap">$${total.toFixed(2)}</td>
      <td class="px-4 py-3 whitespace-nowrap">
        <div class="flex items-center justify-center gap-3">
          <button type="button" class="text-blue-600 edit-btn mr-2" title="Edit"><x-heroicon-o-pencil class="w-4 h-4" /></button>
          <button type="button" class="text-red-600 delete-btn" title="Delete"><x-heroicon-o-trash class="w-4 h-4" /></button>
        </div>
      </td>
    `;

            invoiceItemsTBody.appendChild(tr);

            if (emptyState) emptyState.classList.add("hidden");
            itemsTableWrap.classList.remove("hidden");

            discountForm.reset();
            closeModal();
        });

        // ---- Delete Row ----
        invoiceItemsTBody.addEventListener("click", (e) => {
            const btn = e.target.closest(".delete-btn");
            if (!btn) return;
            const row = btn.closest("tr");
            if (row) row.remove();

            if (!invoiceItemsTBody.querySelector("tr")) {
                itemsTableWrap.classList.add("hidden");
                if (emptyState) emptyState.classList.remove("hidden");
            }
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const refundModal = document.getElementById("refundModalWrapper");
        const refundForm = refundModal.querySelector("form");
        const openBtn = document.getElementById("openRefundModal"); // button that triggers opening
        const closeBtn = document.getElementById("closeRefundModalBtn");
        const cancelBtn = document.getElementById("cancelRefundBtn");

        const itemsTableWrap = document.getElementById("itemsTable");
        const invoiceItemsTBody = document.getElementById("invoiceItems");
        const emptyState = document.getElementById("emptyState");

        const TAX_RATE = 0.098;

        // ---- Modal Open/Close ----
        if (openBtn) {
            openBtn.addEventListener("click", () => {
                refundModal.style.display = "flex";
            });
        }

        const closeModal = () => {
            refundModal.style.display = "none";
        };

        closeBtn.addEventListener("click", closeModal);
        cancelBtn.addEventListener("click", closeModal);

        refundModal.addEventListener("click", (e) => {
            if (e.target === refundModal) closeModal();
        });

        // ---- Add Refund Row ----
        refundForm.addEventListener("submit", function(e) {
            e.preventDefault();

            const amountEl = refundModal.querySelector("#ramount");
            const reasonEl = refundModal.querySelector("#reason");
            const notesEl = refundModal.querySelector("#notes");

            const rawAmount = (amountEl.value || "").trim();
            const reason = (reasonEl.value || "").trim();
            const notes = (notesEl.value || "").trim();

            if (!rawAmount || !reason) {
                alert("Please enter a refund amount and select a reason.");
                return;
            }

            const amount = parseFloat(rawAmount.replace(/[^0-9.]/g, "")) || 0;
            const qty = 1;
            const total = amount + (amount * TAX_RATE);

            const tr = document.createElement("tr");
            tr.innerHTML = `
      <td class="px-4 py-3 whitespace-nowrap">
        <div class="font-medium text-gray-900">${reason}</div>
        ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
      </td>
      <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${qty}</td>
      <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${amount.toFixed(2)}</td>
      <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap">$${total.toFixed(2)}</td>
      <td class="px-4 py-3 whitespace-nowrap">
        <div class="flex items-center justify-center gap-3">
          <button type="button" class="text-blue-600 edit-btn mr-2" title="Edit"><x-heroicon-o-pencil class="w-4 h-4" /></button>
          <button type="button" class="text-red-600 delete-btn" title="Delete"><x-heroicon-o-trash class="w-4 h-4" /></button>
        </div>
      </td>
    `;

            invoiceItemsTBody.appendChild(tr);

            if (emptyState) emptyState.classList.add("hidden");
            itemsTableWrap.classList.remove("hidden");

            refundForm.reset();
            closeModal();
        });

        // ---- Delete Row ----
        invoiceItemsTBody.addEventListener("click", (e) => {
            const btn = e.target.closest(".delete-btn");
            if (!btn) return;
            const row = btn.closest("tr");
            if (row) row.remove();

            if (!invoiceItemsTBody.querySelector("tr")) {
                itemsTableWrap.classList.add("hidden");
                if (emptyState) emptyState.classList.remove("hidden");
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const orderModal = document.getElementById('orderModalWrapper');
        const detailsModal = document.getElementById('viewdetailsModalWrapper');

        const openOrderBtn = document.getElementById('openOrderModal');
        const closeOrderBtn = document.getElementById('closeOrderModalBtn');

        const openDetailsBtn = document.getElementById('openviewdetailsModal');
        const closeDetailsBtn = document.getElementById('closeViewdetailsModalBtn');

        const itemsTableWrap = document.getElementById("itemsTable");
        const invoiceItemsTBody = document.getElementById("invoiceItems");
        const emptyState = document.getElementById("emptyState");

        // --- Order Modal ---
        if (openOrderBtn) openOrderBtn.addEventListener('click', () => orderModal.style.display = 'flex');
        if (closeOrderBtn) closeOrderBtn.addEventListener('click', () => orderModal.style.display = 'none');
        orderModal.addEventListener('click', e => {
            if (e.target === orderModal) orderModal.style.display = 'none';
        });

        // --- View Details Modal ---
        if (openDetailsBtn) openDetailsBtn.addEventListener('click', () => detailsModal.style.display = 'flex');
        if (closeDetailsBtn) closeDetailsBtn.addEventListener('click', () => detailsModal.style.display = 'none');
        detailsModal.addEventListener('click', e => {
            if (e.target === detailsModal) detailsModal.style.display = 'none';
        });

        // Add products into invoice table
        function addProductsToTable(products) {
            products.forEach(p => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-medium text-gray-900">${p.name}</div>
                    <div class="text-gray-500 text-xs"> SKU: ${p.sku} • Order: ${p.orderId}</div>
                    <div class="flex flex-wrap gap-2 mt-1">
                    ${p.extras.map(tag => `<span class="px-2 py-1 text-xs bg-gray-100 rounded">${tag}</span>`).join("")}
                    </div>
                    <div class="text-gray-400 text-xs mt-1">From Order ${p.orderId}</div>
                </td>
                <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${p.qty}</td>
                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${p.unit.toFixed(2)}</td>
                <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap">$${p.total.toFixed(2)}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center justify-center gap-3">
                        <button type="button" class="text-blue-600 edit-btn mr-2"><x-heroicon-o-pencil class="w-4 h-4" /></button>
                        <button type="button" class="text-red-600 delete-btn"><x-heroicon-o-trash class="w-4 h-4" /></button>
                    </div>
                </td>
            `;
                invoiceItemsTBody.appendChild(tr);
            });

            if (emptyState) emptyState.classList.add("hidden");
            itemsTableWrap.classList.remove("hidden");
        }

        // Handle Add to Invoice buttons
        document.body.addEventListener("click", (e) => {
            const btn = e.target.closest(".add-to-invoice-btn");
            if (!btn) return;

            // Example products (replace with your real data)
            const products = [{
                    name: "Premium Widget Set",
                    sku: "PWS-001",
                    qty: 5,
                    unit: 199.99,
                    total: 999.95,
                    extras: ["Color: Blue", "Size: Large", "Material: Aluminum"],
                    orderId: "ORD-2025-001"
                },
                {
                    name: "Installation Service",
                    sku: "INST-001",
                    qty: 1,
                    unit: 250.00,
                    total: 250.00,
                    extras: ["Service Type: On-site Installation", "Technician Level: Senior"],
                    orderId: "ORD-2025-001"
                }
            ];
            addProductsToTable(products);

            // Close modals: if from 2nd modal → close both
            if (btn.closest("#viewdetailsModalWrapper")) {
                detailsModal.style.display = "none";
                orderModal.style.display = "none"; // <-- closes parent too
            } else if (btn.closest("#orderModalWrapper")) {
                orderModal.style.display = "none";
            }
        });

        // Delete row
        invoiceItemsTBody.addEventListener("click", (e) => {
            const btn = e.target.closest(".delete-btn");
            if (!btn) return;
            btn.closest("tr").remove();

            if (!invoiceItemsTBody.querySelector("tr")) {
                itemsTableWrap.classList.add("hidden");
                if (emptyState) emptyState.classList.remove("hidden");
            }
        });
    });
</script>



<script>
    const amountInput = document.getElementById("amount");
    const clueBox = document.getElementById("clueBox");
    const amountPreview = document.getElementById("amountPreview");
    const taxPreview = document.getElementById("taxPreview");
    const totalPreview = document.getElementById("totalPreview");

    amountInput.addEventListener("input", function() {
        this.value = this.value.replace(/[^0-9.]/g, ""); // only digits & dot

        const amount = parseFloat(this.value) || 0;
        const taxRate = 0.098; // 9.8%
        const tax = amount * taxRate;
        const total = amount + tax;

        amountPreview.textContent = `$${amount.toFixed(2)}`;
        taxPreview.textContent = `$${tax.toFixed(2)}`;
        totalPreview.textContent = `$${total.toFixed(2)}`;

        // Show or hide the clue box
        if (this.value.trim() !== "") {
            clueBox.classList.remove("hidden");
        } else {
            clueBox.classList.add("hidden");
        }
    });
</script>

<script>
    function addItemToInvoice() {
        document.getElementById("emptyState").classList.add("hidden");
        document.getElementById("itemsTable").classList.remove("hidden");
    }
</script>
@endpush

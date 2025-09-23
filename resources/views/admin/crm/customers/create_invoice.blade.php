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
        <input type="hidden" value="{{ $sales_tax }}" data-sales-tax-rate>
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
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-700 whitespace-nowrap">Tax</th>
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
                    <span class="font-medium text-gray-900" id="invoice-subtotal">$0.00</span>
                </div>

                <!-- Sales Tax -->
                <!-- Sales Tax -->
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-gray-700">Sales Tax:</span>
                    <span class="text-green-600 font-medium" id="invoice-tax">$0.00</span>
                </div>

                <!-- Divider -->
                <hr class="my-2 border-gray-300">

                <!-- Total -->
                <div class="flex justify-between pt-2 font-bold text-lg">
                    <span>Total:</span>
                    <span class="text-gray-900" id="invoice-total">$0.00</span>
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
                <!-- <form class="space-y-8" id="chargeForm"> -->

                {{ html()->form()->id('chargeForm')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ])->open() }}

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Charge Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 h-[35px] pl-3 flex items-center text-gray-500">$</span>
                        <!-- <input id="amount" type="text" placeholder="0.00" maxlength="8" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"> -->

                        {!! html()->text('amount')->attributes([
                        'placeholder' => '0',
                        'autocomplete' => 'off',
                        'data-digit-input' => 'true',
                        'data-parsley-maxlength' => 8,
                        'maxlength' => 8,
                        ])
                        ->class('pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm')
                        ->placeholder('0.00')->required() !!}

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

                    {!! html()->select('reason', [
                    '' => 'Select charge reason',
                    'New Rental' => 'New Rental',
                    'Rental Extension' => 'Rental Extension',
                    'Damages' => 'Damages',
                    'Fuel Charge' => 'Fuel Charge',
                    'Cleaning Charge' => 'Cleaning Charge',
                    'Missing Items' => 'Missing Items',
                    'Product Purchase' => 'Product Purchase',
                    ], old('reason'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500')
                    ->required() !!}

                </div>

                <!-- Person Responsible -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible </label>

                    {!! html()
                    ->select('responsible_person',
                    $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                    old('responsible_person')
                    )
                    ->id('responsible_person')
                    ->class([
                    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700',
                    'border-red-500' => $errors->has('responsible_person'),
                    ])
                    ->required()
                    !!}

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
                    <!-- <textarea class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="notes" id="notes" rows="3" placeholder="Describe the reason for this charge..."></textarea> -->

                    {!! html()->textarea('notes', old('notes'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                    ->rows(3)
                    ->placeholder('Describe the reason for this charge...') !!}

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

                {{ html()->form()->close() }}
                <!-- </form> -->
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
                <!-- <form class="space-y-8"> -->

                {{ html()->form()->id('discountForm')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ])->open() }}


                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 h-[35px] pl-3 flex items-center text-gray-500">$</span>

                        <!-- <input id="damount" type="text" placeholder="0.00" maxlength="8" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"> -->

                        {!! html()->text('damount')->attributes([
                        'placeholder' => '0',
                        'autocomplete' => 'off',
                        'data-digit-input' => 'true',
                        'data-parsley-maxlength' => 8,
                        'maxlength' => 8,
                        ])
                        ->class('pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm')
                        ->placeholder('0.00')->required() !!}

                    </div>


                </div>
                <!-- Discount Reason -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Reason </label>

                    {!! html()->select('reason', [
                    '' => 'Select discount reason',
                    'Volume Discount' => 'Volume Discount',
                    'Repeat Customer Discount' => 'Repeat Customer Discount',
                    'Damage Waiver Protection' => 'Damage Waiver Protection',
                    'Misc. Management Discount' => 'Misc. Management Discount',
                    'Other' => 'Other',
                    ])
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                    ->required() !!}

                </div>

                <!-- Person Responsible -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible </label>

                    {!! html()
                    ->select('responsible_person',
                    $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                    old('responsible_person')
                    )
                    ->id('responsible_person')
                    ->class([
                    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700',
                    'border-red-500' => $errors->has('responsible_person'),
                    ])
                    ->required()
                    !!}

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

                    <!-- <textarea class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="notes" id="notes" rows="3" placeholder="Describe the reason for this discount..."></textarea> -->

                    {!! html()->textarea('notes', old('notes'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                    ->rows(3)
                    ->placeholder('Describe the reason for this discount...') !!}

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
                {{ html()->form()->close() }}
                <!-- </form> -->
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

                {{ html()->form()->id('refundForm')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ])->open() }}

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Refund Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 h-[35px] pl-3 flex items-center text-gray-500">$</span>
                        <!-- <input id="ramount" type="text" placeholder="0.00" maxlength="8" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"> -->
                        {!! html()->text('ramount')->attributes([
                        'placeholder' => '0',
                        'autocomplete' => 'off',
                        'data-digit-input' => 'true',
                        'data-parsley-maxlength' => 8,
                        'maxlength' => 8,
                        ])
                        ->class('pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm')
                        ->placeholder('0.00')->required() !!}
                    </div>
                </div>
                <!-- Refund Reason -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Refund Reason</label>

                    {!! html()->select('reason', [
                    '' => 'Select refund reason',
                    'Damaged Item' => 'Damaged Item',
                    'Wrong Item Shipped' => 'Wrong Item Shipped',
                    'Damage Waiver Protection' => 'Damage Waiver Protection',
                    'Customer Cancellation' => 'Customer Cancellation',
                    'Billing Overcharge' => 'Billing Overcharge',
                    'Duplicate Charge' => 'Duplicate Charge',
                    'Other' => 'Other',
                    ])
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                    ->required() !!}

                </div>

                <!-- Person Responsible -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>

                    {!! html()
                    ->select('responsible_person',
                    $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                    old('responsible_person')
                    )
                    ->id('responsible_person')
                    ->class([
                    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700',
                    'border-red-500' => $errors->has('responsible_person'),
                    ])
                    ->required()
                    !!}

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
                {{ html()->form()->close() }}
                <!-- </form> -->
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

            <div class="px-6 pb-6 overflow-y-auto">
                <div class="overflow-x-auto rounded-lg shadow border border-gray-200 ">
                    <table class="w-full text-left" id="orders-table">
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

                            @foreach ($orders as $order)
                            <tr>
                                <td class="px-4 py-3 font-medium whitespace-nowrap"> {!! $order->view_link !!} </td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $order->created_at->format(config('app.date.date_format')) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{!! $order->products->pluck('product_name')->join('<br> ') !!}</td>
                                <td class="px-4 py-3 font-semibold whitespace-nowrap">{{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {!! \App\Helpers\CustomHelper::statusBadge($order->last_payment_status) !!}
                                </td>
                                <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                                    <a class="text-blue-600 gap-2 inline-flex items-center justify-center" href="{{ route('admin.order-management.orders.edit', $order->unique_id) }}" id="openviewdetailsModal" target="_blank"> <x-heroicon-o-eye class="w-4 h-4" /> View Details</a>
                                    <button class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 add-to-invoice-btn" data-order-id="{{ $order->unique_id }}">
                                        + Add to Invoice
                                    </button>
                                </td>
                            </tr>
                            @endforeach

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

    // Select the hidden input by its data attribute
    const salesTaxInput = document.querySelector('[data-sales-tax-rate]');

    // Assign its value (converted to a number) to the global variable
    window.SALES_TAX_RATE = parseFloat(salesTaxInput.value) || 0;

    // Global array to store all product data
    const invoice_data = [];

    // Global function to add products to the invoice_data array
    function addInvoiceProduct(products) {
        products.forEach(p => {
            const product = {
                ...p, // Spread operator to copy all properties from the original product object
                type: 'order', // Add the 'type' property
            };
            invoice_data.push(product); // Add the new product object to the array
        });
        console.log('Current Invoice Data:', invoice_data); // Log the array to check
    }

    (function() {
        // Helper to recalculate invoice totals dynamically
        function updateInvoiceSummary() {
            const subtotalElem = document.querySelector('#invoice-subtotal');
            const totalElem = document.querySelector('#invoice-total');
            const taxElem = document.querySelector('#invoice-tax');
            const createBtn = document.querySelector('button[name="action"][value="save_new"]');
            const invoiceItemsTBody = document.getElementById("invoiceItems"); // Make sure to get this element

            let subtotal = 0;
            let totalTax = 0;

            const rows = invoiceItemsTBody.querySelectorAll("tr");

            rows.forEach(row => {

                const type = row.dataset.type; // "charge", "order", "discount", "refund"

                const priceCell = row.querySelector("td:nth-child(3)"); // This should be the price per item
                const taxCell = row.querySelector("td:nth-child(4)"); // This should be the tax amount

                let price = parseFloat(priceCell?.textContent.replace('$', '')) || 0;
                let tax = parseFloat(taxCell?.textContent.replace('$', '')) || 0;

                if (type === "charge" || type === "order") {
                    subtotal += price;
                    totalTax += tax;
                } else if (type === "discount" || type === "refund") {
                    subtotal -= price;
                    totalTax -= tax;
                }
            });

            //  Check for negative values and set to 0 if found 
            subtotal = Math.max(0, subtotal);

            if (subtotal == 0) {
                totalTax = 0;
            } else {
                totalTax = Math.max(0, totalTax);
            }

            let finalTotal = subtotal + totalTax;
            finalTotal = Math.max(0, finalTotal);

            if (subtotalElem) subtotalElem.textContent = `$${subtotal.toFixed(2)}`;
            if (taxElem) taxElem.textContent = totalTax > 0 ? `$${totalTax.toFixed(2)}` : 'Tax Exempt';
            if (totalElem) totalElem.textContent = `$${finalTotal.toFixed(2)}`;

            if (createBtn) {
                createBtn.disabled = rows.length === 0;
            }
        }

        // Attach the function to the window object to make it global
        window.updateInvoiceSummary = updateInvoiceSummary;
    })();
</script>

<!-- openChargeModal -->
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

            // Explicitly reset your custom data property
            if (amountInput.digits) {
                amountInput.digits = "";
            }

            // Also reset the displayed value and preview
            amountInput.value = "";

            amountPreview.textContent = "$0.00";
            taxPreview.textContent = "$0.00";
            totalPreview.textContent = "$0.00";

            // Use a small delay to ensure the DOM has updated
            setTimeout(() => {
                $(form).parsley().reset();
            }, 50);
            clueBox.classList.add("hidden");
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // preview calculation
        amountInput.addEventListener("input", function() {
            this.value = this.value.replace(/[^0-9.]/g, "");
            const amount = parseFloat(this.value) || 0;
            const taxRate = window.SALES_TAX_RATE;
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

            // Check if the form is valid using Parsley.js
            if ($(form).parsley().isValid()) {

                const desc = document.getElementById("reason").value || "New Charge";
                const notes = document.getElementById("notes").value.trim();
                const price = parseFloat(amountInput.value) || 0;
                const qty = 1;
                const taxRate = window.SALES_TAX_RATE;
                const taxPrice = price * taxRate;
                const total = price + taxPrice;

                if (price <= 0) {
                    notyf.error("Enter valid amount");
                    return;
                }

                const row = document.createElement("tr");
                row.dataset.type = "charge";
                row.innerHTML = `
            <td class="px-4 py-3 whitespace-nowrap">
                <div class="font-medium text-gray-900">${desc} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-600 text-white">
                                                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-3 h-3">
                            <path d="M5 12h14"></path>
                            <path d="M12 5v14"></path>
                        </svg><span class="ml-1 capitalize text-xs">charge</span>
                                      </span></div>
                ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
            </td>
            <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${qty}</td>
            <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${price.toFixed(2)}</td>
            <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${taxPrice.toFixed(2)}</td>
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

                window.updateInvoiceSummary();

            } else {
                console.log("Form is not valid.");
            }
        });
    });
</script>
<!-- openChargeModal -->

<!-- discount  -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const discountModal = document.getElementById("discountModalWrapper");
        const discountForm = discountModal.querySelector("form");
        const itemsTableWrap = document.getElementById("itemsTable");
        const invoiceItemsTBody = document.getElementById("invoiceItems");
        const emptyState = document.getElementById("emptyState"); // if present

        const discountamountInput = discountModal.querySelector("#damount");

        const openBtn = document.getElementById("openDiscountModal");
        const closeBtn = document.getElementById("closeDiscountModalBtn");
        const cancelBtn = document.getElementById("cancelDiscountBtn");

        // ---- Modal Open/Close ----
        openBtn.addEventListener("click", () => {
            discountModal.style.display = "flex";
        });

        const closeModal = () => {
            discountModal.style.display = 'none';
            discountForm.reset();

            // Explicitly reset your custom data property
            if (discountamountInput.digits) {
                discountamountInput.digits = "";
            }

            // Also reset the displayed value and preview
            discountamountInput.value = "";

            // Use a small delay to ensure the DOM has updated
            setTimeout(() => {
                $(discountForm).parsley().reset();
            }, 50);
            clueBox.classList.add("hidden");
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
                notyf.error("Please enter a discount amount and select a reason.");
                return;
            }

            const amount = parseFloat(rawAmount.replace(/[^0-9.]/g, "")) || 0;
            const qty = 1;
            const total = amount; // adjust if needed

            const tr = document.createElement("tr");

            tr.dataset.type = "discount";

            tr.innerHTML = `
      <td class="px-4 py-3 whitespace-nowrap">
        <div class="font-medium text-gray-900">${reason} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-600 text-white">
                                                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-3 h-3">
                                                    <circle cx="12" cy="8" r="6"></circle>
                                                    <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                                                    </svg>      
                                                                                              <span class="ml-1 capitalize text-xs">Discount</span>
                                      </span></div>
        ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
      </td>
      <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${qty}</td>
      <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${amount.toFixed(2)}</td>
       <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$0.00</td>
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

            // Attach the function to the window object to make it global
            window.updateInvoiceSummary();

            closeModal();
        });


    });
</script>
<!-- discount  -->

<!-- refundModalWrapper -->
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

        const refundamountInput = refundModal.querySelector("#ramount");

        const TAX_RATE = window.SALES_TAX_RATE;

        // ---- Modal Open/Close ----
        if (openBtn) {
            openBtn.addEventListener("click", () => {
                refundModal.style.display = "flex";
            });
        }
        const closeModal = () => {
            refundModal.style.display = 'none';
            refundForm.reset();

            // Explicitly reset your custom data property
            if (refundamountInput.digits) {
                refundamountInput.digits = "";
            }

            // Also reset the displayed value and preview
            refundamountInput.value = "";

            // Use a small delay to ensure the DOM has updated
            setTimeout(() => {
                $(refundForm).parsley().reset();
            }, 50);
            clueBox.classList.add("hidden");
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
                notyf.error("Please enter a refund amount and select a reason.");
                return;
            }

            const taxRate = window.SALES_TAX_RATE;
            const amount = parseFloat(rawAmount.replace(/[^0-9.]/g, "")) || 0;
            const qty = 1;
            const taxPrice = amount * taxRate;
            const total = amount + taxPrice;

            // const taxPrice = price * taxRate;
            // const total = price + taxPrice;

            const tr = document.createElement("tr");
            tr.dataset.type = "refund";
            tr.innerHTML = `
      <td class="px-4 py-3 whitespace-nowrap">
        <div class="font-medium text-gray-900">${reason} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-600 text-white">
                       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-3 h-3">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>        <span class="ml-1 capitalize text-xs">refund</span>
                                      </span> </div>
        ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
      </td>
      <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${qty}</td>
      <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${amount.toFixed(2)}</td>
      <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${taxPrice.toFixed(2)}</td>
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
            window.updateInvoiceSummary();
            refundForm.reset();
            closeModal();
        });
    });
</script>
<!-- refundModalWrapper -->

<!-- orderModalWrapper -->
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

            // Use the global function to add products to the data array
            addInvoiceProduct(products);

            products.forEach(p => {
                const tr = document.createElement("tr");

                tr.dataset.type = "order";

                tr.innerHTML = `
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-medium text-gray-900">${p.name} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-600 text-white">
                                                                                                   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart w-3 h-3">
                            <circle cx="8" cy="21" r="1"></circle>
                            <circle cx="19" cy="21" r="1"></circle>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                        </svg> <span class="ml-1 capitalize text-xs">Order</span>
                                      </span></div>
                    <div class="text-gray-500 text-xs"> SKU: ${p.sku} • Order: ${p.orderId}</div>
                    <div class="flex flex-wrap gap-2 mt-1">
                    ${p.extras.map(tag => `<span class="px-2 py-1 text-xs bg-gray-100 rounded">${tag}</span>`).join("")}
                    </div>
                    <div class="text-gray-400 text-xs mt-1">From Order ${p.orderId}</div>
                </td>
                <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${p.qty}</td>
                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${p.unit.toFixed(2)}</td>
                       <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${p.tax.toFixed(2)}</td>
                <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap">$${p.total.toFixed(2)}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center justify-center gap-3">

                        <button type="button" class="text-red-600 delete-btn"><x-heroicon-o-trash class="w-4 h-4" /></button>
                    </div>
                </td>
            `;
                invoiceItemsTBody.appendChild(tr);
            });

            if (emptyState) emptyState.classList.add("hidden");
            itemsTableWrap.classList.remove("hidden");

            window.updateInvoiceSummary();
        }

        // Handle Add to Invoice buttons
        document.body.addEventListener("click", async (e) => { // <--- async here
            const btn = e.target.closest(".add-to-invoice-btn");
            if (!btn) return;

            const orderId = btn.dataset.orderId;

            // console.log('orderId', orderId);

            const orderDetailsBaseUrl = "{{ route('admin.crm.customers.invoice.orders.details', ['unique_id' => ':id']) }}";


            const wrapper = document.getElementById('orders-table'); // loader wrapper
            if (wrapper) {
                wrapper.classList.add('opacity-50', 'pointer-events-none'); // show loader
            }

            try {
                const url = orderDetailsBaseUrl.replace(':id', orderId);
                const res = await fetch(url, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json"
                    }
                });

                if (!res.ok) {
                    throw new Error(`HTTP error ${res.status}`);
                }

                let data;
                try {
                    data = await res.json();
                } catch (jsonErr) {
                    throw new Error("Response was not valid JSON");
                }

                if (data.success === false) {
                    throw new Error(data.message || "Server returned an error");
                }



                const products = (data.products || []).map(p => ({
                    name: p.name,
                    sku: p.sku ?? "-",
                    qty: p.qty,
                    unit: parseFloat(p.unit_price),
                    tax: parseFloat(p.tax) || 0,
                    total: parseFloat(p.total),
                    extras: (p.extras || []).filter(x => x),
                    orderId: data.unique_id
                }));


                addProductsToTable(products);

                // Close modals: if from 2nd modal → close both
                if (btn.closest("#viewdetailsModalWrapper")) {
                    detailsModal.style.display = "none";
                    orderModal.style.display = "none";
                } else if (btn.closest("#orderModalWrapper")) {
                    orderModal.style.display = "none";
                }

            } catch (error) {
                console.error(" Failed to fetch order products:", error);
                notyf.error(error.message || "Unable to load order products.");
            } finally {
                // Always remove loader
                if (wrapper) {
                    wrapper.classList.remove('opacity-50', 'pointer-events-none');
                }
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

            window.updateInvoiceSummary();
        });
    });
</script>
<!-- orderModalWrapper -->

<script>
    const amountInput = document.getElementById("amount");
    const clueBox = document.getElementById("clueBox");
    const amountPreview = document.getElementById("amountPreview");
    const taxPreview = document.getElementById("taxPreview");
    const totalPreview = document.getElementById("totalPreview");

    amountInput.addEventListener("input", function() {
        this.value = this.value.replace(/[^0-9.]/g, ""); // only digits & dot

        const amount = parseFloat(this.value) || 0;
        const taxRate = window.SALES_TAX_RATE;
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
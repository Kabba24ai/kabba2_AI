@extends('admin.layouts.app')

@section('title', 'Customer Invoice')

@push('css')
@endpush

@section('content')

@include('flash::message')
@include('admin.partials.formErrors')

@php
$isEdit = isset($invoice) && $invoice->id;
$invoiceNumber = $isEdit ? $invoice->invoice_number : \App\Helpers\CustomHelper::generateInvoiceNumber();

@endphp

{{ html()->form()->id('invoiceForm')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ]) ->action($isEdit
        ? route('admin.crm.customers.invoice.update', $invoice->unique_id)
        : route('admin.crm.customers.invoice.store',$customer->unique_id)
    )
    ->method('POST')->open() }}

<input type="hidden" name="invoice_data" id="invoiceDataInput" value="{{ old('invoice_data', $invoiceItems ?? '') }}">
<input type="hidden" name="subtotal" id="invoiceSubtotalInput" value="{{ old('subtotal', $invoice->subtotal ?? 0) }}">
<input type="hidden" name="tax" id="invoiceTaxInput" value="{{ old('tax', $invoice->sales_tax ?? 0) }}">
<input type="hidden" name="total" id="invoiceTotalInput" value="{{ old('total', $invoice->total ?? 0) }}">
<input type="hidden" name="customer_id" id="customer_id" value="{{ $customer->id }}">


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
                <h1 class="text-2xl font-bold text-gray-900"> {{ $isEdit ? 'Edit Invoice' : 'Create Invoice' }}</h1>
                <p class="text-sm text-gray-500">Invoice <span id="invoiceNumberDisplay">#{{ $invoiceNumber }}</span> for {{ $customer->company_name ?? '' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button onclick="closeWindow()" type="button" name="action" value="close" class="inline-flex items-center px-6 py-2 rounded-md text-gray-700 bg-white text-sm font-medium shadow transition"> Cancel
            </button>

            <button type="submit" name="action" value="save_new" disabled="" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-sm font-medium shadow transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save w-4 h-4 mr-2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                {{ $isEdit ? 'Update' : 'Create' }} Invoice
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
                        <!-- <input id="invoiceNumberInput" name="invoicenumber" type="text" class=" w-full border border-gray-300 rounded-md px-3 py-2 text-sm" readonly value="{{ \App\Helpers\CustomHelper::generateInvoiceNumber() }}"> -->

                        {!! html()->text('invoice_number')->attributes([
                        'id' => 'invoiceNumberInput',
                        'readonly' => true,
                        ])
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                        ->value($invoiceNumber )
                        ->required() !!}

                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Date</label>
                        <!-- <input name="invoice_date" class="w-full border rounded-md datepicker px-3 py-2 text-sm bg-white text-gray-700 border-gray-300" type="text" name="invoice_date" id="invoice_date" placeholder="MM-DD-YYYY" autocomplete="off"> -->

                        {!! html()->text('invoice_date',old('invoice_date', \App\Helpers\CustomHelper::formatDate($invoice->invoice_date ?? null) ?? null))->class([
                        'w-full border rounded-md datepicker px-3 py-2 text-sm bg-white text-gray-700 border-gray-300',
                        'border-red-500' => $errors->has('account_application_completed'),
                        'border-gray-300' => !$errors->has('account_application_completed'),
                        ])->attributes([
                        'id' => 'invoice_date',
                        'placeholder' => 'MM-DD-YYYY',
                        'autocomplete' => 'off',
                        ])->required() !!}

                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <!-- <input name="due_date" class="w-full border rounded-md datepicker px-3 py-2 text-sm bg-white text-gray-700 border-gray-300" type="text" name="due_date" id="due_date" placeholder="MM-DD-YYYY" autocomplete="off"> -->

                        {!! html()->text('due_date',old('due_date', \App\Helpers\CustomHelper::formatDate($invoice->due_date ?? null) ?? null))->class([
                        'w-full border rounded-md datepicker px-3 py-2 text-sm bg-white text-gray-700 border-gray-300',
                        'border-red-500' => $errors->has('account_application_completed'),
                        'border-gray-300' => !$errors->has('account_application_completed'),
                        ])->attributes([
                        'id' => 'due_date',
                        'placeholder' => 'MM-DD-YYYY',
                        'autocomplete' => 'off',
                        ])
                        !!}

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

                            @php
                            // Make sure $invoiceItems is always an array
                            if (is_string($invoiceItems)) {
                            $invoiceItemsArray = json_decode($invoiceItems, true) ?? [];
                            } elseif ($invoiceItems instanceof \Illuminate\Support\Collection) {
                            $invoiceItemsArray = $invoiceItems->toArray();
                            } else {
                            $invoiceItemsArray = $invoiceItems ?? [];
                            }

                            // Filter out 'order' types
                            $invoiceItems_encoded = array_filter($invoiceItemsArray, fn($item) => $item['type'] !== 'order');
                            @endphp


                            @if(!empty($invoiceItems_encoded))
                            @foreach($invoiceItems_encoded as $item)

                            @php
                            // Determine badge color based on type
                            $badgeColor=match($item['type']) { 'charge'=> 'red',
                            'order' => 'green',
                            'discount' => 'purple',
                            'refund' => 'blue',
                            default => 'red',
                            };


                            // Set custom SVG for each type
                            $badgeSvg = match($item['type']) {
                            'charge' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-3 h-3">
                                <path d="M5 12h14"></path>
                                <path d="M12 5v14"></path>
                            </svg>',
                            'discount' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-3 h-3">
                                <circle cx="12" cy="8" r="6"></circle>
                                <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                            </svg>',
                            'refund' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-3 h-3">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                <polyline points="16 7 22 7 22 13"></polyline>
                            </svg>',
                            default => '<svg class="lucide lucide-plus w-3 h-3"></svg>',
                            };
                            @endphp


                            @if ($item['type'] !== 'order')
                            <tr data-type="{{ $item['type'] }}" data-id="{{ $item['id'] }}">


                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-900"> {{ $item['name'] }} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $badgeColor }}-600 text-white">
                                            {!! $badgeSvg !!}
                                            <span class="ml-1 capitalize text-xs"> {{ $item['type'] }}</span>
                                        </span>
                                    </div>
                                    @if(!empty($item['notes']))
                                    <div class="text-gray-500 text-sm">{{ $item['notes'] }}</div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center text-sm whitespace-nowrap">{{ $item['qty'] }}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">{{ \App\Helpers\CustomHelper::formatCurrency($item['unit']) }}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap"> {{ \App\Helpers\CustomHelper::formatCurrency($item['tax']) }} </td>
                                <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap"> {{ \App\Helpers\CustomHelper::formatCurrency($item['total']) }}</td>
                                <td class="px-4 py-3 text-center h-full items-center justify-center gap-3 whitespace-nowrap">
                                    <button type="button" class="text-blue-600 edit-btn mr-2"><x-heroicon-o-pencil class="w-4 h-4" /></button>
                                    <button type="button" class="text-red-600 delete-btn"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                </td>


                            </tr>
                            @endif
                            @endforeach
                            @endif

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoice Notes -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-semibold mb-4">Invoice Notes</h3>
                <textarea rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" name="invoice_notes" placeholder="Enter any additional notes for this invoice..."></textarea>
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

                @if ($isEdit)
                <div class="flex items-center mt-3 mb-2">
                    <label for="invoice_status" class="text-sm font-medium mr-2">Invoice Status:</label>

                    {{-- Invoice Status --}}
                    {!! html()->select('invoice_status', [
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                    ], old('invoice_status', $invoice->invoice_status ?? 'pending'))
                    ->class([
                    ' w-2/6 border rounded-md lg:px-1 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('invoice_status'),
                    'border-gray-300' => !$errors->has('invoice_status'),
                    ])
                    ->id('invoice_status') !!}



                </div>
                @endif


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

{{ html()->form()->close() }}
<!-- New Charge Wrapper -->
@include('admin.crm.customers.partials._invoice_charge')

<!-- New Discount Wrapper -->
@include('admin.crm.customers.partials._invoice_discount')

<!-- New Refund Wrapper -->
@include('admin.crm.customers.partials._invoice_refund')

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
                <button id="closeOrderModalBtn" type="button" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
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
                                    <button type="button" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 add-to-invoice-btn" data-order-id="{{ $order->unique_id }}">
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
                <button type="button" id="closeViewdetailsModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
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
                    <button type="button" class="bg-green-600 text-white px-6 py-2 rounded-md add-to-invoice-btn text-sm">
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
    // --- PRELOAD from PHP ---
    const invoice_data = []; // pre-filled if edit page

    // Global function to add products to the invoice_data array
    function addInvoiceProduct(products, type) {
        products.forEach(p => {
            const product = {
                ...p, // Spread operator to copy all properties from the original product object
                type: type, // Add the 'type' property
            };
            invoice_data.push(product); // Add the new product object to the array
        });
        console.table(invoice_data);
    }

    // generate a unique ID for every arry item

    function generateUniqueId(prefix = "item") {
        return prefix + "_" + Date.now() + "_" + Math.floor(Math.random() * 100000);
    }


    function parseCurrency(value) {
        // Remove everything except digits, minus sign, and dot
        return Number(value.replace(/[^0-9.-]+/g, "")) || 0;
    }


    const invoiceItemsTBody = document.getElementById("invoiceItems"); // Make sure to get this element

    (function() {
        // Helper to recalculate invoice totals dynamically
        function updateInvoiceSummary() {
            const subtotalElem = document.querySelector('#invoice-subtotal');
            const totalElem = document.querySelector('#invoice-total');
            const taxElem = document.querySelector('#invoice-tax');
            const createBtn = document.querySelector('button[name="action"][value="save_new"]');

            const subtotalInput = document.getElementById("invoiceSubtotalInput");
            const taxInput = document.getElementById("invoiceTaxInput");
            const totalInput = document.getElementById("invoiceTotalInput");
            const invoiceDataInput = document.getElementById("invoiceDataInput");

            let subtotal = 0;
            let totalTax = 0;

            const rows = invoiceItemsTBody.querySelectorAll("tr");

            rows.forEach(row => {

                const type = row.dataset.type; // "charge", "order", "discount", "refund"

                const priceCell = row.querySelector("td:nth-child(3)"); // This should be the price per item
                const taxCell = row.querySelector("td:nth-child(4)"); // This should be the tax amount

                // let price = parseFloat(priceCell?.textContent.replace('$', '')) || 0;
                // let tax = parseFloat(taxCell?.textContent.replace('$', '')) || 0;

                let price = parseCurrency(priceCell?.textContent || '0');
                let tax = parseCurrency(taxCell?.textContent || '0');

                // Log row info

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

            //  Update hidden inputs for form submission
            if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
            if (taxInput) taxInput.value = totalTax.toFixed(2);
            if (totalInput) totalInput.value = finalTotal.toFixed(2);
            if (invoiceDataInput) invoiceDataInput.value = JSON.stringify(invoice_data);

        }


        // Attach the function to the window object to make it global
        window.updateInvoiceSummary = updateInvoiceSummary;
    })();
</script>
<script>
    // Pass directly from Blade
    const existingOrderItems = @json($orderItems ?? []);
    const existingOtherItems = @json($otherItems ?? []);

    console.table(existingOtherItems);

    // Add existingOtherItems into the global invoice_data array
    if (Array.isArray(existingOtherItems) && existingOtherItems.length > 0) {
        existingOtherItems.forEach(item => {
            invoice_data.push(item); // Just add to the array, no table rendering
        });
        console.log("Added existingOtherItems to invoice_data:");
        console.table(invoice_data);
    }
</script>



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



        // Render existing order items if in edit mode
        if (existingOrderItems.length > 0) {
            addProductsToTable(existingOrderItems);
        }

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
            addInvoiceProduct(products, 'order');

            products.forEach(p => {
                const tr = document.createElement("tr");

                tr.dataset.type = "order";
                tr.dataset.id = p.id;
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
                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${Number(p.unit).toFixed(2)}</td>
                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${Number(p.tax).toFixed(2)}</td>
                <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap">$${Number(p.total).toFixed(2)}</td>

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
                    id: p.order_products_unique_id,
                    name: p.name,
                    sku: p.sku ?? "-",
                    qty: p.qty,
                    unit: parseFloat(p.unit_price),
                    tax: parseFloat(p.tax) || 0,
                    total: parseFloat(p.total),
                    extras: (p.extras || []).filter(x => x),
                    orderId: data.unique_id,
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

            const row = btn.closest("tr");
            const itemId = row.dataset.id;

            // Remove row
            row.remove();

            // Remove from global array
            const index = invoice_data.findIndex(p => p.id === itemId);
            if (index !== -1) {
                invoice_data.splice(index, 1);
            }

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

<script>
    function closeWindow() {
        // Attempt to close the current window
        window.close();

        // Fallback: if window.close() is blocked, redirect to about:blank
        setTimeout(() => {
            if (!window.closed) {
                window.location.href = 'about:blank';
            }
        }, 100);
    }
</script>

@endpush

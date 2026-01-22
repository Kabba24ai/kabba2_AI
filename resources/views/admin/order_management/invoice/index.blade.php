@extends('admin.layouts.app', ['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'System Configuration')

@section('content')

<div class="min-h-screen  flex flex-col items-center">
    <!-- Header -->
    <div class="text-center mb-6">
        <h2 class="text-xl font-semibold">Receipt Preview</h2>
        <p class="text-gray-500 text-sm">
            This is how your receipt will look when printed on 8.5" × 11" paper
        </p>
    </div>

    <!-- Receipt Card -->
    <div class="bg-white w-full max-w-3xl shadow-md rounded-md p-6 rounded-md shadow-sm ">
        <!-- Top Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-blue-600 flex items-center justify-center rounded-lg text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building2 w-8 h-8 text-white"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>
                </div>
                <div>
                    <h3 class="font-semibold text-2xl">RentalPro</h3>
                    <p class="text-sm text-gray-500">Professional Rentals & Sales</p>
                </div>
            </div>
            <div class="text-right mt-4 sm:mt-0">
                <h3 class="text-2xl font-bold">RECEIPT</h3>
                <p class="text-sm text-gray-500">#REC-2025-001234</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm border-b-2 border-gray-800 pb-4 mb-6">
            <!-- Left Column -->
            <div class="text-gray-600 space-y-1">
                <p class="font-medium text-gray-700 text-lg">From:</p>
                <p>RentalPro Solutions Inc.</p>
                <p>123 Business Avenue</p>
                <p>New York, NY 10001</p>
                <p>(555) 123-4567</p>
                <p>contact@rentalpro.com</p>
            </div>

            <!-- Right Column -->
            <div class="space-y-3">
                <div>
                    <p class="flex items-center gap-2 font-semibold text-gray-900 ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-5 h-5 text-gray-600"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path></svg>
                        Transaction Date:
                    </p>
                    <p class="text-lg font-medium text-gray-900">January 15, 2025</p>
                </div>

                <div>
                    <p class="font-semibold text-gray-900">Customer PO:</p>
                    <p class="text-lg font-medium text-gray-900">PO-2025-5678</p>
                </div>
            </div>
        </div>

        <!-- Bill To Label -->
        <div>
            <p class="font-medium mb-2 flex items-center space-x-2 text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-5 h-5"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>Bill To:</span>
            </p>
        </div>
        <!-- Customer Card -->
        <div class=" p-4">
            <p class="font-medium text-lg text-gray-900">John Smith</p>
            <p class="text-gray-700 font-medium">Smith Construction LLC</p>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-600">
                <!-- Phone -->
                <div class="flex text-md items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-4 h-4"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <span>(555) 987-6543</span>
                </div>

                <!-- Address -->
                <div class="flex text-md items-start space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin w-4 h-4 mt-1"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span>456 Oak Street<br>Los Angeles, CA 90210</span>
                </div>

                <!-- Email -->
                <div class="flex text-md items-center space-x-2 sm:col-span-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail w-4 h-4"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                    <span>john.smith@smithconstruction.com</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <!-- Table Head -->
                <thead>
                    <tr class="border-b border-gray-300 mb-4">
                        <th class="text-left py-2 font-semibold text-gray-700 whitespace-nowrap">Item</th>
                        <th class="text-center py-2 font-semibold text-gray-700 whitespace-nowrap">Qty</th>
                        <th class="text-right py-2 font-semibold text-gray-700 whitespace-nowrap">Unit Price</th>
                        <th class="text-right py-2 font-semibold text-gray-700 whitespace-nowrap">Total</th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody>
                    <!-- Row 1 -->
                    <tr>
                        <td class="pt-2 pb-2 align-top whitespace-nowrap">
                            <div class="font-medium text-gray-900">Heavy Duty Excavator Rental</div>
                        </td>
                        <td class="pt-2 text-center align-top whitespace-nowrap">2</td>
                        <td class="pt-2 text-right align-top whitespace-nowrap">$450.00</td>
                        <td class="pt-2 text-right align-top font-semibold whitespace-nowrap">$900.00</td>
                    </tr>
                    <tr>
                        <td class="pl-6 pb-2 text-gray-500 text-sm whitespace-nowrap">+ Insurance Coverage</td>
                        <td></td>
                        <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">$85.00</td>
                        <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">$85.00</td>
                    </tr>
                    <tr class="border-b ">
                        <td class="pl-6 pb-2 text-gray-500 text-sm whitespace-nowrap">+ Delivery & Pickup</td>
                        <td></td>
                        <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">$125.00</td>
                        <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">$125.00</td>
                    </tr>

                    <!-- Row 2 -->
                    <tr>
                        <td class="pt-2 pb-2 align-top">
                            <div class="font-medium text-gray-900 whitespace-nowrap">Safety Equipment Package</div>
                        </td>
                        <td class="pt-2 text-center align-top">1</td>
                        <td class="pt-2 text-right align-top whitespace-nowrap">$75.00</td>
                        <td class="pt-2 text-right align-top font-semibold whitespace-nowrap">$75.00</td>
                    </tr>
                    <tr class="border-b pb-2">
                        <td class="pl-6 pb-2 text-gray-500 text-sm whitespace-nowrap">+ Extended Coverage Plan</td>
                        <td></td>
                        <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">$25.00</td>
                        <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">$25.00</td>
                    </tr>

                    <!-- Row 3 -->
                    <tr class="border-b">
                        <td class="py-3 font-medium text-gray-900 whitespace-nowrap">Portable Generator</td>
                        <td class="text-center">1</td>
                        <td class="text-right whitespace-nowrap">$120.00</td>
                        <td class="text-right font-semibold whitespace-nowrap">$120.00</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totals Section -->
        <div class="mt-6 text-sm">
            <div class="flex justify-between py-1">
                <span class="text-gray-700 text-md">Subtotal:</span>
                <span>$1,330.00</span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-gray-700">Tax (8.8%):</span>
                <span>$116.37</span>
            </div>
            <div class="flex justify-between border-t mt-2 pt-2 font-bold text-lg">
                <span>Total:</span>
                <span>$1,446.38</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-sm text-gray-500 border-t border-gray-800">
            <p class="mt-6">Thank you for your business!</p>
            <p>For questions about this receipt, contact us at (555) 123-4567</p>
        </div>
        <!-- Buttons -->
        <div class="mt-6 flex flex-col sm:flex-row justify-center gap-3">
            <button class="bg-blue-600 hover:bg-blue-700 text-white gap-2 text-sm px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Receipt
            </button>
            <button class="bg-green-600 hover:bg-green-700 text-white gap-2 text-sm px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Email Receipt
            </button>
            <button class="bg-purple-600 hover:bg-purple-700 text-white gap-2 text-sm px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Download PDF
            </button>
        </div>
    </div>
</div>
@endsection

@push('js')


@endpush
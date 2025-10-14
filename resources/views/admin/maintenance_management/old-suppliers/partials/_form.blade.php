<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Supplier Information</h2>
    </div>
    
    <div class="p-6 space-y-8">
        {{-- Basic Information --}}
        <div>
            <h3 class="text-base font-medium text-gray-900 mb-4">Basic Information</h3>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Supplier Name
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $supplier->name ?? '') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('name') border-red-500 @enderror"
                        placeholder="Enter supplier name"
                    />
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Account Number
                    </label>
                    <input
                        type="text"
                        name="account_number"
                        value="{{ old('account_number', $supplier->account_number ?? '') }}"
                        maxlength="20"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('account_number') border-red-500 @enderror"
                        placeholder="Vendor ID"
                    />
                    @error('account_number')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select
                        name="status"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('status') border-red-500 @enderror"
                    >
                        <option value="Active" {{ old('status', $supplier->status ?? 'Active') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $supplier->status ?? '') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Company Address --}}
        <div>
            <h3 class="text-base font-medium text-gray-900 mb-4">Company Address</h3>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Street Address
                    </label>
                    <input
                        type="text"
                        name="street_address"
                        value="{{ old('street_address', $supplier->street_address ?? '') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('street_address') border-red-500 @enderror"
                        placeholder="123 Main Street"
                    />
                    @error('street_address')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        City
                    </label>
                    <input
                        type="text"
                        name="city"
                        value="{{ old('city', $supplier->city ?? '') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('city') border-red-500 @enderror"
                        placeholder="City"
                    />
                    @error('city')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        State
                    </label>
                    <select
                        name="state"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('state') border-red-500 @enderror"
                    >
                        <option value="">Select State</option>
                        @foreach(['AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'] as $state)
                            <option value="{{ $state }}" {{ old('state', $supplier->state ?? '') === $state ? 'selected' : '' }}>{{ $state }}</option>
                        @endforeach
                    </select>
                    @error('state')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ZIP Code
                    </label>
                    <input
                        type="text"
                        name="zip_code"
                        value="{{ old('zip_code', $supplier->zip_code ?? '') }}"
                        maxlength="10"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('zip_code') border-red-500 @enderror"
                        placeholder="12345"
                    />
                    @error('zip_code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tax ID
                    </label>
                    <input
                        type="text"
                        name="tax_id"
                        value="{{ old('tax_id', $supplier->tax_id ?? '') }}"
                        maxlength="15"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('tax_id') border-red-500 @enderror"
                        placeholder="Tax ID"
                    />
                    @error('tax_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Business Contact --}}
        <div>
            <h3 class="text-base font-medium text-gray-900 mb-4">Business Contact</h3>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Phone
                    </label>
                    <input
                        type="tel"
                        name="main_phone"
                        value="{{ old('main_phone', $supplier->main_phone ?? '') }}"
                        maxlength="14"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('main_phone') border-red-500 @enderror"
                        placeholder="(555) 123-4567"
                    />
                    @error('main_phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        name="main_email"
                        value="{{ old('main_email', $supplier->main_email ?? '') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('main_email') border-red-500 @enderror"
                        placeholder="contact@supplier.com"
                    />
                    @error('main_email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Website URL
                    </label>
                    <input
                        type="url"
                        name="website"
                        value="{{ old('website', $supplier->website ?? '') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('website') border-red-500 @enderror"
                        placeholder="https://www.example.com"
                    />
                    @error('website')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Contact Sections --}}
        @php
        $contactSections = [
            'sales' => 'Sales',
            'inside_sales' => 'Inside Sales', 
            'technical' => 'Technical Support',
            'parts' => 'Parts'
        ];
        @endphp

        @foreach($contactSections as $section => $label)
        <div>
            <h3 class="text-base font-medium text-gray-900 mb-4">{{ $label }}</h3>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Name
                    </label>
                    <input
                        type="text"
                        name="{{ $section }}_name"
                        value="{{ old($section.'_name', $supplier->{$section.'_name'} ?? '') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error($section.'_name') border-red-500 @enderror"
                        placeholder="{{ $label }} contact name"
                    />
                    @error($section.'_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Phone
                    </label>
                    <input
                        type="tel"
                        name="{{ $section }}_phone"
                        value="{{ old($section.'_phone', $supplier->{$section.'_phone'} ?? '') }}"
                        maxlength="14"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error($section.'_phone') border-red-500 @enderror"
                        placeholder="(555) 123-4567"
                    />
                    @error($section.'_phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Cell
                    </label>
                    <input
                        type="tel"
                        name="{{ $section }}_cell"
                        value="{{ old($section.'_cell', $supplier->{$section.'_cell'} ?? '') }}"
                        maxlength="14"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error($section.'_cell') border-red-500 @enderror"
                        placeholder="(555) 123-4567"
                    />
                    @error($section.'_cell')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        name="{{ $section }}_email"
                        value="{{ old($section.'_email', $supplier->{$section.'_email'} ?? '') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error($section.'_email') border-red-500 @enderror"
                        placeholder="{{ $section }}@supplier.com"
                    />
                    @error($section.'_email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
        @endforeach

        {{-- Business Terms --}}
        <div>
            <h3 class="text-base font-medium text-gray-900 mb-4">Business Terms</h3>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Payment Terms
                    </label>
                    <select
                        name="payment_terms"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('payment_terms') border-red-500 @enderror"
                    >
                        @foreach(['Net 30', 'Net 60', 'COD', 'Credit Card', 'Wire Transfer'] as $term)
                            <option value="{{ $term }}" {{ old('payment_terms', $supplier->payment_terms ?? 'Net 30') === $term ? 'selected' : '' }}>{{ $term }}</option>
                        @endforeach
                    </select>
                    @error('payment_terms')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Shipping Terms
                    </label>
                    <select
                        name="shipping_terms"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('shipping_terms') border-red-500 @enderror"
                    >
                        @foreach(['FOB Origin', 'FOB Destination', 'Expedited', 'Standard'] as $term)
                            <option value="{{ $term }}" {{ old('shipping_terms', $supplier->shipping_terms ?? 'FOB Origin') === $term ? 'selected' : '' }}>{{ $term }}</option>
                        @endforeach
                    </select>
                    @error('shipping_terms')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div>
            <h3 class="text-base font-medium text-gray-900 mb-4">Notes</h3>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12">
                    <textarea
                        name="notes"
                        rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('notes') border-red-500 @enderror"
                        placeholder="Additional notes about this supplier..."
                    >{{ old('notes', $supplier->notes ?? '') }}</textarea>
                    @error('notes')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Form Actions --}}
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
        <a href="{{ route('admin.maintenance-management.suppliers.index') }}" 
           class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            Cancel
        </a>
        <button
            type="submit"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
        >
            {{ isset($supplier) ? 'Update Supplier' : 'Create Supplier' }}
        </button>
    </div>
</div>

{{-- JavaScript for phone formatting --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone formatting for all phone/cell fields
    const phoneFields = document.querySelectorAll('input[type="tel"]');
    
    phoneFields.forEach(field => {
        field.addEventListener('input', function(e) {
            const value = e.target.value.replace(/\D/g, '');
            let formatted = '';
            
            if (value.length > 0) {
                if (value.length <= 3) {
                    formatted = `(${value}`;
                } else if (value.length <= 6) {
                    formatted = `(${value.slice(0, 3)}) ${value.slice(3)}`;
                } else {
                    formatted = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
                }
            }
            
            e.target.value = formatted;
        });
    });
});
</script>

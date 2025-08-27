{{-- Equipment Form --}}
<div class="space-y-6">
    {{-- First Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Basic Information --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-4">
                <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-bold text-gray-900">Basic Information</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Equipment Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="equipment_name"
                        value="{{ old('equipment_name', $equipment->equipment_name ?? '') }}"
                        placeholder="CAT 320 Excavator"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('equipment_name') border-red-300 bg-red-50 @else border-gray-300 @enderror"
                    />
                    @error('equipment_name')
                        <p class="mt-1 text-xs text-red-600 flex items-center space-x-1">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="category"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white @error('category') border-red-300 bg-red-50 @else border-gray-300 @enderror"
                    >
                        <option value="">Select</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->title }}" {{ old('category', $equipment->category ?? '') === $category->title ? 'selected' : '' }}>{{ $category->title }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-xs text-red-600 flex items-center space-x-1">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Equipment Hours
                    </label>
                    <input
                        type="text"
                        name="equipment_hours"
                        value="{{ old('equipment_hours', $equipment->equipment_hours ?? '') }}"
                        placeholder="10.5"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    />

                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Equipment ID <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="equipment_id"
                        value="{{ old('equipment_id', $equipment->equipment_id ?? '') }}"
                        placeholder="EXC-001"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('equipment_id') border-red-300 bg-red-50 @else border-gray-300 @enderror"
                    />
                    @error('equipment_id')
                        <p class="mt-1 text-xs text-red-600 flex items-center space-x-1">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Equipment Details --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-4">
                <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0M15 17a2 2 0 104 0" />
                </svg>
                <h3 class="text-lg font-bold text-gray-900">Equipment Details</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Brand <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="brand"
                        value="{{ old('brand', $equipment->brand ?? '') }}"
                        placeholder="Caterpillar"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('brand') border-red-300 bg-red-50 @else border-gray-300 @enderror"
                    />
                    @error('brand')
                        <p class="mt-1 text-xs text-red-600 flex items-center space-x-1">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input
                        type="text"
                        name="model"
                        value="{{ old('model', $equipment->model ?? '') }}"
                        placeholder="320"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model Year</label>
                    <input
                        type="number"
                        name="model_year"
                        value="{{ old('model_year', $equipment->model_year ?? '') }}"
                        placeholder="2023"
                        min="1900"
                        max="{{ date('Y') + 1 }}"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('model_year') border-red-300 bg-red-50 @else border-gray-300 @enderror"
                    />
                    @error('model_year')
                        <p class="mt-1 text-xs text-red-600 flex items-center space-x-1">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Acquired</label>
                    <input
    type="text"
    name="date_acquired"
    placeholder="mm/dd/yyyy"
    value="{{ old('date_acquired', optional($equipment->date_acquired)->format('Y-m-d')) }}"
    style="max-width: 200px;"
    class="w-full px-3 py-2 datepicker text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
/>
                </div>
            </div>
        </div>
    </div>

    {{-- Second Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Financial & Legal --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-4">
                <svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
                <h3 class="text-lg font-bold text-gray-900">Financial & Legal</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Cost</label>
                    <div class="relative" style="max-width: 200px;">
                        <svg class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                        <input
                            type="number"
                            name="cost"
                            value="{{ old('cost', $equipment->cost ?? '') }}"
                            placeholder="0.00"
                            min="0"
                            step="0.01"
                            class="w-full pl-8 pr-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('cost') border-red-300 bg-red-50 @else border-gray-300 @enderror"
                        />
                    </div>
                    @error('cost')
                        <p class="mt-1 text-xs text-red-600 flex items-center space-x-1">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ownership Type</label>
                    <select
                        name="ownership_type"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                    >
                        <option value="">Select</option>
                        <option value="owned" {{ old('ownership_type', $equipment->ownership_type ?? '') === 'owned' ? 'selected' : '' }}>Owned</option>
                        <option value="financed" {{ old('ownership_type', $equipment->ownership_type ?? '') === 'financed' ? 'selected' : '' }}>Financed</option>
                        <option value="leased" {{ old('ownership_type', $equipment->ownership_type ?? '') === 'leased' ? 'selected' : '' }}>Leased</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Finance Company</label>
                    <input
                        type="text"
                        name="finance_company"
                        value="{{ old('finance_company', $equipment->finance_company ?? '') }}"
                        placeholder="Bank/Lender"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Term (Months)</label>
                    <input
                        type="number"
                        name="term"
                        value="{{ old('term', $equipment->term ?? '') }}"
                        placeholder="60"
                        min="1"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Interest Rate (%)</label>
                    <input
                        type="number"
                        name="rate"
                        value="{{ old('rate', $equipment->rate ?? '') }}"
                        placeholder="5.25"
                        min="0"
                        step="0.01"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Payment</label>
                    <div class="relative" style="max-width: 200px;">
                        <svg class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                        <input
                            type="number"
                            name="monthly_payment"
                            value="{{ old('monthly_payment', $equipment->monthly_payment ?? '') }}"
                            placeholder="0.00"
                            min="0"
                            step="0.01"
                            class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        />
                    </div>
                </div>
            </div>
        </div>

        {{-- Identification Numbers --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-4">
                <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                </svg>
                <h3 class="text-lg font-bold text-gray-900">Identification Numbers</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">VIN</label>
                    <input
                        type="text"
                        name="vin"
                        value="{{ old('vin', $equipment->vin ?? '') }}"
                        placeholder="Vehicle Identification Number"
                        style="max-width: 175px;"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors font-mono"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                    <input
                        type="text"
                        name="serial_number"
                        value="{{ old('serial_number', $equipment->serial_number ?? '') }}"
                        placeholder="Serial Number"
                        style="max-width: 200px;"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors font-mono"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">License Plate</label>
                    <input
                        type="text"
                        name="plate"
                        value="{{ old('plate', $equipment->plate ?? '') }}"
                        placeholder="ABC-1234"
                        style="max-width: 175px;"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IMEI (GPS)</label>
                    <div class="relative" style="max-width: 200px;">
                        <svg class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <input
                            type="text"
                            name="imei"
                            value="{{ old('imei', $equipment->imei ?? '') }}"
                            placeholder="GPS Tracker IMEI"
                            class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors font-mono"
                        />
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <p class="text-xs text-gray-500">GPS tracker ID for location tracking</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Third Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Power Source --}}
        <!--
         <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-3 mb-4">
                <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <h3 class="text-lg font-bold text-gray-900">Power Source</h3>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Power Type</label>
                <select id="power-type-select" name="power_source_type"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                    <option value="">Select Power Source</option>
                    <option value="diesel">Diesel</option>
                    <option value="gas">Gas</option>
                    <option value="batteries">Batteries</option>
                </select>
            </div>

            <div id="power-type-fields" class="mt-4"></div>
        </div>
    -->
        {{-- Power Source --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-3 mb-4">
        <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        <h3 class="text-lg font-bold text-gray-900">Power Source</h3>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Power Type <span class="text-red-500">*</span></label>
        <select
            id="power-type-select"
            name="power_source_type"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
        >
            <option value="">Select Power Source</option>
            <option value="diesel" {{ old('power_source_type', $equipment->power_source_type ?? '') === 'diesel' ? 'selected' : '' }}>Diesel</option>
            <option value="gas" {{ old('power_source_type', $equipment->power_source_type ?? '') === 'gas' ? 'selected' : '' }}>Gas</option>
            <option value="batteries" {{ old('power_source_type', $equipment->power_source_type ?? '') === 'batteries' ? 'selected' : '' }}>Batteries</option>
        </select>
         @error('power_source_type')
                        <p class="mt-1 text-xs text-red-600 flex items-center space-x-1">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
    </div>

    <!-- Dynamic fields get inserted here -->
    <div id="power-type-fields" class="mt-4">
        @if(old('power_source_type', $equipment->power_source_type ?? ''))
            @include('admin.maintenance_management.equipment.partials.power_source_fields', [
                'powerSourceType' => old('power_source_type', $equipment->power_source_type ?? ''),
                'equipment' => $equipment
            ])
        @endif
    </div>
</div>

        {{-- Customer Checklist --}}
         {{-- Customer Checklist --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
        <h3 class="text-lg font-bold text-gray-900">Checklist Master</h3>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Checklist Master</label>
        <select
            name="checklist_master"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
        >
            <option value="">Select Checklist Master</option>
            @foreach($customerChecklist as $checklist)
                <option value="{{ $checklist }}" {{ old('checklist_master', $equipment->checklist_master ?? '') === $checklist ? 'selected' : '' }}>{{ $checklist }}</option>
            @endforeach
        </select>
        <p class="mt-2 text-xs text-gray-500">
            Customer delivery and return inspection checklist
        </p>
    </div>
</div>


         {{-- Equipment Service --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench h-5 w-5 text-indigo-600"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
        <h3 class="text-lg font-bold text-gray-900">Equipment Service</h3>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Service Schedule</label>
        <select
            name="equipment_service_list"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
        >
            <option value="">Select Equipment Service List</option>
            @foreach($equipmentServiceList as $val)
                <option value="{{ $val }}" {{ old('equipment_service_list', $equipment->equipment_service_list ?? '') === $val ? 'selected' : '' }}>{{ $val }}</option>
            @endforeach
        </select>
        <p class="mt-2 text-xs text-gray-500">
            Maintenance schedule and service history tracking
        </p>
    </div>
</div>
        {{-- Equipment Part List --}}
         <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-3 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package h-5 w-5 text-purple-600"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
        <h3 class="text-lg font-bold text-gray-900">Equipment Parts List</h3>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Parts List Template</label>
        <select
            name="equipment_parts_list"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
        >
            <option value="">Select Equipment Part List</option>
            @foreach($equipmentPartsList as $parts)
                <option value="{{ $parts }}" {{ old('equipment_parts_list', $equipment->equipment_parts_list ?? '') === $parts ? 'selected' : '' }}>{{ $parts }}</option>
            @endforeach
        </select>
        <p class="mt-2 text-xs text-gray-500">
            Assign a parts list template for this equipment
        </p>
    </div>
</div>
    </div>

    {{-- Equipment Notes - Full Width --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center space-x-2 mb-4">
            <svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-lg font-bold text-gray-900">Equipment Notes</h3>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Additional Notes and Comments
            </label>
            <textarea
                name="equipment_notes"
                rows="4"
                placeholder="Enter any additional notes, maintenance history, special instructions, or other relevant information about this equipment..."
                class="tinymce w-full px-4 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
            >{{ old('equipment_notes', $equipment->equipment_notes ?? '') }}</textarea>
        </div>
    </div>
</div>



@push('js')

<script>
    window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));
</script>


<!-- Added by Jignesh Parmar -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const hasDefCheckbox = document.getElementById('has-def-checkbox');
    const defField = document.getElementById('def-capacity-field');

    if (hasDefCheckbox) {
        hasDefCheckbox.addEventListener('change', function () {
            defField.style.display = this.checked ? 'block' : 'none';
        });
    }
});


document.addEventListener('DOMContentLoaded', function () {
    const powerTypeSelect = document.getElementById('power-type-select');
    const fieldsContainer = document.getElementById('power-type-fields');

    const templates = {
        diesel: `
    <div class="flex items-center space-x-2 py-2.5">
        <input type="checkbox" name="has_def" id="has-def-checkbox"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
        <label class="text-sm font-medium text-gray-700">Has DEF (Diesel Exhaust Fluid)</label>
    </div>
    <div>
        <label class="jsp block text-sm font-medium text-gray-700 mb-1">Diesel Tank Capacity (Gallons)</label>
        <input type="number" name="diesel_tank_capacity" placeholder="0" min="0" max="100000" step="0.1"
            class="jsp w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2
                   focus:ring-blue-500 focus:border-blue-500 transition-colors" value="">
    </div>
    <div id="def-capacity-field" style="display:none;">
        <label class="block text-sm font-medium text-gray-700 mb-1" style="padding-top:15px">DEF Tank Capacity (Gallons)</label>
        <input type="number" name="def_capacity"  placeholder="0" min="0" max="100000" step="0.1"
            class="jsp w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2
                   focus:ring-blue-500 focus:border-blue-500 transition-colors" value="">
    </div>`,

        gas: `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gas Tank Capacity (Gallons)</label>
                <input type="number" name="gas_tank_capacity" placeholder="0" min="0" step="0.1"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    value="">
            </div>
        `,
        batteries: `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Standard Battery Count</label>
                <input type="number" name="standard_battery_count" placeholder="0" min="0" step="1"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    value="">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expanded Battery Count</label>
                <input type="number" name="expanded_battery_count" placeholder="0" min="0" step="1"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    value="">
            </div>
        `
    };

    powerTypeSelect.addEventListener('change', function () {
    const selected = this.value;
    fieldsContainer.innerHTML = templates[selected] || '';

    if (selected === 'diesel') {
        const hasDefCheckbox = document.getElementById('has-def-checkbox');
        const defField = document.getElementById('def-capacity-field');
        hasDefCheckbox.addEventListener('change', function () {
            defField.style.display = this.checked ? 'block' : 'none';
        });
        // Moved the input restriction code here
                const input = document.querySelector('input[name="diesel_tank_capacity"]');
                input.addEventListener('input', function() {
                    if (this.value.length > 6) {
                        if(this.value > 100000)
                    {
                        this.value = 100000;
                    }
                    else
                    {
                        this.value = this.value.slice(0, 6);
                    }

                    }
                });
                const defInput = document.querySelector('input[name="def_capacity"]');
                 defInput.addEventListener('input', function() {
                    if (this.value.length > 6) {
                        if(this.value > 100000)
                    {
                        this.value = 100000;
                    }
                    else
                    {
                        this.value = this.value.slice(0, 6);
                    }
                    }
                });
    }
});




});
</script>
<!-- End By Jignesh Parmar -->

<script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
@vite('resources/admin/js/tinymce.js')
@endpush

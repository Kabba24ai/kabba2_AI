@extends('admin.layouts.app')

@section('title', 'Equipment Summary Worksheet')

@section('content')
    <div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
        <div class="flex-1 overflow-auto p-6">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.maintenance-management.equipment.index') }}"
                            class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to Equipment
                        </a>
                    </div>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">Equipment Summary Worksheet</h1>
                    <p class="text-sm text-gray-600">Review and update core equipment parameters in a spreadsheet view.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
                <form method="GET" class="grid grid-cols-1 gap-3 lg:grid-cols-12 lg:items-center">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search name, equipment id, brand or model"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 lg:col-span-4" />

                    <select name="category"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 lg:col-span-2">
                        <option value="">All categories</option>
                        @foreach ($categories as $id => $title)
                            <option value="{{ $id }}" @selected((string) request('category') === (string) $id)>
                                {{ $title }}
                            </option>
                        @endforeach
                    </select>

                    <select name="store"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 lg:col-span-2">
                        <option value="">All stores</option>
                        @foreach ($stores as $id => $name)
                            <option value="{{ $id }}" @selected((string) request('store') === (string) $id)>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 lg:col-span-2">
                        <option value="">All statuses</option>
                        <option value="available" @selected(request('status') === 'available')>Available</option>
                        <option value="rented" @selected(request('status') === 'rented')>Rented</option>
                        <option value="maintenance" @selected(request('status') === 'maintenance')>Maintenance</option>
                        <option value="damaged" @selected(request('status') === 'damaged')>Damaged</option>
                    </select>

                    <div class="flex flex-wrap items-center justify-end gap-2 lg:col-span-2 lg:flex-nowrap">
                        <a href="{{ route('admin.maintenance-management.equipment.worksheet') }}"
                            class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Clear
                        </a>
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <form method="POST" action="{{ route('admin.maintenance-management.equipment.worksheet.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm text-gray-600">Showing {{ $equipment->firstItem() ?? 0 }} - {{ $equipment->lastItem() ?? 0 }} of
                        {{ $equipment->total() }} equipment records.</p>
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Save Worksheet Changes
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-[4200px] divide-y divide-gray-200 text-sm">
                        <thead class="sticky top-0 z-10 bg-gray-100">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-700">
                                <th class="px-3 py-3">Equipment Name</th>
                                <th class="px-3 py-3">Category</th>
                                <th class="px-3 py-3">Hours</th>
                                <th class="px-3 py-3">Overage Rate</th>
                                <th class="px-3 py-3">Equipment ID</th>
                                <th class="px-3 py-3">Store</th>
                                <th class="px-3 py-3">Brand</th>
                                <th class="px-3 py-3">Model</th>
                                <th class="px-3 py-3">Model Year</th>
                                <th class="px-3 py-3">Date Acquired</th>
                                <th class="px-3 py-3">Starting Mechanism</th>
                                <th class="px-3 py-3">Value</th>
                                <th class="px-3 py-3">Purchase Cost</th>
                                <th class="px-3 py-3">Freight / Shipping</th>
                                <th class="px-3 py-3">Taxes / Fees</th>
                                <th class="px-3 py-3">Ownership Type</th>
                                <th class="px-3 py-3">Down Payment</th>
                                <th class="px-3 py-3">Amount Financed</th>
                                <th class="px-3 py-3">Finance Company</th>
                                <th class="px-3 py-3">Term (Months)</th>
                                <th class="px-3 py-3">Interest Rate (%)</th>
                                <th class="px-3 py-3">Monthly Payment</th>
                                <th class="px-3 py-3">VIN</th>
                                <th class="px-3 py-3">Serial Number</th>
                                <th class="px-3 py-3">License Plate</th>
                                <th class="px-3 py-3">IMEI (GPS)</th>
                                <th class="px-3 py-3">Warranty Duration (Months)</th>
                                <th class="px-3 py-3">Warranty Duration (Hours)</th>
                                <th class="px-3 py-3">COI Submitted</th>
                                <th class="px-3 py-3">Not For Rent</th>
                                <th class="px-3 py-3">Checklist Master</th>
                                <th class="px-3 py-3">Parts List Templates</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($equipment as $item)
                                @php
                                    $row = old('rows.' . $item->unique_id, []);
                                @endphp
                                <tr class="align-top">
                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][equipment_name]"
                                            value="{{ data_get($row, 'equipment_name', $item->equipment_name) }}"
                                            class="w-56 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.equipment_name') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <select name="rows[{{ $item->unique_id }}][product_category_id]"
                                            class="w-52 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.product_category_id') ? 'border-red-500' : 'border-gray-300' }}">
                                            <option value="">Select</option>
                                            @foreach ($categories as $id => $title)
                                                <option value="{{ $id }}" @selected((string) data_get($row, 'product_category_id', $item->product_category_id) === (string) $id)>
                                                    {{ $title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="0.01"
                                            name="rows[{{ $item->unique_id }}][equipment_hours]"
                                            value="{{ data_get($row, 'equipment_hours', $item->equipment_hours) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.equipment_hours') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="0.01"
                                            name="rows[{{ $item->unique_id }}][overage_rate]"
                                            value="{{ data_get($row, 'overage_rate', $item->overage_rate) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.overage_rate') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][equipment_id]"
                                            value="{{ data_get($row, 'equipment_id', $item->equipment_id) }}"
                                            class="w-40 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.equipment_id') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <select name="rows[{{ $item->unique_id }}][store_id]"
                                            class="w-44 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.store_id') ? 'border-red-500' : 'border-gray-300' }}">
                                            <option value="">None</option>
                                            @foreach ($stores as $id => $name)
                                                <option value="{{ $id }}" @selected((string) data_get($row, 'store_id', $item->store_id) === (string) $id)>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][brand]"
                                            value="{{ data_get($row, 'brand', $item->brand) }}"
                                            class="w-40 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.brand') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][model]"
                                            value="{{ data_get($row, 'model', $item->model) }}"
                                            class="w-32 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.model') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" name="rows[{{ $item->unique_id }}][model_year]" min="1900"
                                            max="{{ date('Y') + 1 }}"
                                            value="{{ data_get($row, 'model_year', $item->model_year) }}"
                                            class="w-24 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.model_year') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][date_acquired]"
                                            value="{{ data_get($row, 'date_acquired', $item->date_acquired ? \App\Helpers\CustomHelper::formatDate($item->date_acquired) : '') }}"
                                            data-format="{{ config('app.date.js_date_format') }}" placeholder="mm/dd/yyyy"
                                            class="datepicker w-40 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.date_acquired') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <select name="rows[{{ $item->unique_id }}][key_starting_mechanism]"
                                            class="w-44 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.key_starting_mechanism') ? 'border-red-500' : 'border-gray-300' }}">
                                            <option value="">Select</option>
                                            @foreach (['none' => 'None', '1_key' => '1 Key', '2_keys' => '2 Keys', 'key_pad' => 'Key Pad (Code Provided)', 'pull_cord' => 'Pull Cord'] as $value => $label)
                                                <option value="{{ $value }}" @selected((string) data_get($row, 'key_starting_mechanism', $item->key_starting_mechanism?->value ?? $item->key_starting_mechanism) === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="0.01"
                                            name="rows[{{ $item->unique_id }}][equipment_value]"
                                            value="{{ data_get($row, 'equipment_value', $item->equipment_value) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.equipment_value') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="0.01"
                                            name="rows[{{ $item->unique_id }}][purchase_cost]"
                                            value="{{ data_get($row, 'purchase_cost', $item->purchase_cost) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.purchase_cost') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="0.01"
                                            name="rows[{{ $item->unique_id }}][freight_shipping]"
                                            value="{{ data_get($row, 'freight_shipping', $item->freight_shipping) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.freight_shipping') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="0.01"
                                            name="rows[{{ $item->unique_id }}][taxes_fees]"
                                            value="{{ data_get($row, 'taxes_fees', $item->taxes_fees) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.taxes_fees') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <select name="rows[{{ $item->unique_id }}][ownership_type]"
                                            class="w-36 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.ownership_type') ? 'border-red-500' : 'border-gray-300' }}">
                                            <option value="">Select</option>
                                            <option value="owned" @selected((string) data_get($row, 'ownership_type', $item->ownership_type) === 'owned')>Owned</option>
                                            <option value="financed" @selected((string) data_get($row, 'ownership_type', $item->ownership_type) === 'financed')>Financed</option>
                                            <option value="leased" @selected((string) data_get($row, 'ownership_type', $item->ownership_type) === 'leased')>Leased</option>
                                        </select>
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="0.01"
                                            name="rows[{{ $item->unique_id }}][down_payment]"
                                            value="{{ data_get($row, 'down_payment', $item->down_payment) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.down_payment') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="0.01"
                                            name="rows[{{ $item->unique_id }}][amount_financed]"
                                            value="{{ data_get($row, 'amount_financed', $item->amount_financed) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.amount_financed') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][finance_company]"
                                            value="{{ data_get($row, 'finance_company', $item->finance_company) }}"
                                            class="w-48 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.finance_company') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="1" step="1"
                                            name="rows[{{ $item->unique_id }}][term_in_months]"
                                            value="{{ data_get($row, 'term_in_months', $item->term_in_months) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.term_in_months') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="0.01"
                                            name="rows[{{ $item->unique_id }}][interest_rate]"
                                            value="{{ data_get($row, 'interest_rate', $item->interest_rate) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.interest_rate') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="0.01"
                                            name="rows[{{ $item->unique_id }}][monthly_payment]"
                                            value="{{ data_get($row, 'monthly_payment', $item->monthly_payment) }}"
                                            class="w-28 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.monthly_payment') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][vehicle_identification_number]"
                                            value="{{ data_get($row, 'vehicle_identification_number', $item->vehicle_identification_number) }}"
                                            class="w-48 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.vehicle_identification_number') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][serial_number]"
                                            value="{{ data_get($row, 'serial_number', $item->serial_number) }}"
                                            class="w-44 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.serial_number') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][license_plate]"
                                            value="{{ data_get($row, 'license_plate', $item->license_plate) }}"
                                            class="w-32 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.license_plate') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][imei]"
                                            value="{{ data_get($row, 'imei', $item->imei) }}"
                                            class="w-44 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.imei') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="1"
                                            name="rows[{{ $item->unique_id }}][warranty_duration_months]"
                                            value="{{ data_get($row, 'warranty_duration_months', $item->warranty_duration_months) }}"
                                            class="w-24 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.warranty_duration_months') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="1"
                                            name="rows[{{ $item->unique_id }}][warranty_duration_hours]"
                                            value="{{ data_get($row, 'warranty_duration_hours', $item->warranty_duration_hours) }}"
                                            class="w-24 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.warranty_duration_hours') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $item->unique_id }}][coi_submitted]"
                                            value="{{ data_get($row, 'coi_submitted', $item->coi_submitted ? \App\Helpers\CustomHelper::formatDate($item->coi_submitted) : '') }}"
                                            data-format="{{ config('app.date.js_date_format') }}" placeholder="mm/dd/yyyy"
                                            class="datepicker w-40 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.coi_submitted') ? 'border-red-500' : 'border-gray-300' }}" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="hidden" name="rows[{{ $item->unique_id }}][not_for_rent]" value="0">
                                        <input type="checkbox" name="rows[{{ $item->unique_id }}][not_for_rent]" value="1"
                                            {{ (int) data_get($row, 'not_for_rent', (int) $item->not_for_rent) === 1 ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <select name="rows[{{ $item->unique_id }}][checklist_master_id]"
                                            class="w-52 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.checklist_master_id') ? 'border-red-500' : 'border-gray-300' }}">
                                            <option value="">Select Checklist Template</option>
                                            @foreach ($checklistMasters as $id => $name)
                                                <option value="{{ $id }}" @selected((string) data_get($row, 'checklist_master_id', $item->checklist_master_id) === (string) $id)>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td class="px-3 py-2">
                                        <select name="rows[{{ $item->unique_id }}][parts_list_id]"
                                            class="w-52 rounded border px-2 py-1.5 {{ $errors->has('rows.' . $item->unique_id . '.parts_list_id') ? 'border-red-500' : 'border-gray-300' }}">
                                            <option value="">Select Parts List</option>
                                            @foreach ($partsLists as $id => $name)
                                                <option value="{{ $id }}" @selected((string) data_get($row, 'parts_list_id', $item->parts_list_id) === (string) $id)>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="32" class="px-4 py-8 text-center text-sm text-gray-500">No equipment found for
                                        current filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($errors->any())
                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Please review highlighted worksheet cells. Some rows contain invalid values.
                    </div>
                @endif

                <div class="mt-4 flex items-center justify-between">
                    <div>{{ $equipment->links() }}</div>
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Save Worksheet Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

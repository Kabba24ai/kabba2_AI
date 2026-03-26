@extends('admin.layouts.app')

@section('title', 'Create Customer')

@push('css')
@endpush

@section('content')
    @include('flash::message')

    <div class="bg-gray-50 px-4 py-4 border-b border-gray-200 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <a href="{{ route('admin.crm.kabba-ai-customers.index') }}"
                   class="flex items-center text-gray-600 hover:text-gray-800 transition">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-1" />
                    <span class="text-sm font-medium">Back to Customers</span>
                </a>

                <div class="hidden sm:block h-6 border-l border-gray-300"></div>

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create Customer</h1>
                    <p class="text-sm text-gray-500">Add a new kabba.ai customer record</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto">
        <form action="{{ route('admin.crm.kabba-ai-customers.store') }}" method="POST"
              class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required placeholder="Enter first name"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('first_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                    <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required placeholder="Enter last name"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('last_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required placeholder="Enter email address"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone <span class="text-red-500">*</span></label>
                    <input id="phone_number" name="phone_number" type="text" value="{{ old('phone_number') }}" required placeholder="Enter phone number"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('phone_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-700 mb-2">Business Name <span class="text-red-500">*</span></label>
                    <input id="business_name" name="business_name" type="text" value="{{ old('business_name') }}" required placeholder="Enter business name"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('business_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                    <input id="amount" name="amount" type="number" min="0" step="0.01" value="4.95" readonly
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-gray-100 text-gray-700 cursor-not-allowed" />
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="street_address" class="block text-sm font-medium text-gray-700 mb-2">Street Address</label>
                    <input id="street_address" name="street_address" type="text" value="{{ old('street_address') }}" placeholder="Enter street address"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('street_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <input id="city" name="city" type="text" value="{{ old('city') }}" placeholder="Enter city"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('city')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">State</label>
                    <input id="state" name="state" type="text" value="{{ old('state') }}" placeholder="Enter state"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('state')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="zip_code" class="block text-sm font-medium text-gray-700 mb-2">Zip Code</label>
                    <input id="zip_code" name="zip_code" type="text" value="{{ old('zip_code') }}" placeholder="Enter zip code"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('zip_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Payment Status <span class="text-red-500">*</span></label>
                    @php $selectedStatus = old('status', 'pending'); @endphp
                    <select id="status" name="status" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ $selectedStatus === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="setup_status" class="block text-sm font-medium text-gray-700 mb-2">Setup Status <span class="text-red-500">*</span></label>
                    @php $selectedSetup = old('setup_status', 'pending'); @endphp
                    <select id="setup_status" name="setup_status" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="pending" {{ $selectedSetup === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ $selectedSetup === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ $selectedSetup === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    @error('setup_status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">Comment</label>
                <textarea id="comment" name="comment" rows="6"
                          placeholder="Add comment"
                          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('comment') }}</textarea>
                @error('comment')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-8 flex items-center justify-end gap-3">
                <a href="{{ route('admin.crm.kabba-ai-customers.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center px-5 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium">
                    Create Customer
                </button>
            </div>
        </form>
    </div>
@endsection

@push('js')
@endpush

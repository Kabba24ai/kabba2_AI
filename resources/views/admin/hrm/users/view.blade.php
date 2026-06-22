@extends('admin.layouts.app')

@section('title', 'View User')

@section('content')

@include('flash::message')


<div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Left Section -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <!-- Back to Employees -->
            <a href="{{route('admin.hrm.users.index')}}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path>
                </svg>
                <span class="text-sm font-medium">Back to Employees</span>
            </a>

            <!-- Divider -->
            <div class="hidden sm:block h-6 border-l border-gray-300"></div>

            <!-- Customer Info -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900"> {{ $user->full_name }} </h1>
                <p class="text-sm text-gray-500">View employee information and settings</p>
            </div>
        </div>
            @php
                $loguser = auth()->user()->fresh();
            @endphp

                   @if(in_array('master_admin', $loguser->role_short_names))
        <!-- Right Section: Buttons -->
        <div class="flex flex-wrap gap-2">
            <div class="inline">
                <a href="{{ route('admin.hrm.users.edit',$user->unique_id ) }}" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                    Edit Employee
                </a>
            </div>
        </div>
        @endif
    </div>
</div>


<div class=" bg-white rounded-lg shadow border p-6 flex flex-col sm:flex-row  gap-4 mt-6">

    <!-- Profile Icon -->
    <div class="flex-shrink-0">
        <div class="w-16 h-16 bg-blue-100 text-blue-600 flex items-center justify-center rounded-full">
            <!-- Heroicon: User -->
            <x-heroicon-o-user class="w-8 h-8 text-blue-600" />
        </div>
    </div>

    <!-- Info -->
    <div class="flex-1">
        <h2 class="text-lg font-semibold text-gray-900">{{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }} </h2>

        <div class="flex flex-wrap gap-4 text-sm text-gray-600 mt-1">
            <div class="flex items-center gap-1">
                <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                {{ $user->email ?? '' }}
            </div>
            <div class="flex items-center gap-1">
                <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                {{ \App\Helpers\CustomHelper::formatPhone($user->mobile_phone ?? '') ?: ' ' }}
            </div>
            <div class="flex items-center gap-1">
                <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                {{ $user->street_address }} {{ $user->city }} {{ optional($user->stateRelation)->name }} {{ $user->zip_code }} {{ $user->country }}
            </div>
        </div>

        <!-- Tags -->
        <div class="mt-3 flex flex-wrap gap-2">

            @if($user->status === 'Active')
            <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
            @elseif($user->status === 'Inactive')
            <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">inactive</span>
            @endif



            @if($user->pay_type && isset($paytypes[$user->pay_type]))
            <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ $paytypes[$user->pay_type] }}
            </span>
            @endif




            @foreach ($user->roles as $role)
            <span class="px-3 py-1 rounded-full text-xs font-medium" style="background-color: {{ $role->color }}20; color: {{ $role->color }};">
                {{ $role->name }}
            </span>
            @endforeach

        </div>
    </div>
</div>


<div class=" grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 ">
    <!-- Personal Information -->
    <div class="bg-white p-5 rounded-lg border shadow">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-user class="w-5 h-5 text-blue-600" />
            Personal Information
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 text-sm text-gray-700">
            <div>
                <p class="text-xs text-gray-500 font-medium">Name</p>
                <p class="text-sm text-gray-900">{{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Email Address</p>
                <p class="text-sm text-gray-900"> {{ $user->email ?? 'Not provided' }} </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Mobile Phone</p>
                <p class="text-sm text-gray-900">{{ $user->mobile_phone ?? 'Not provided' }} </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Phone Number</p>
                <p class="text-sm text-gray-900">{{ $user->phone_number ?? 'Not provided' }}</p>
            </div>
            <div >
                <p class="text-xs text-gray-500 font-medium">Address</p>
                <p class="text-sm text-gray-900">
                    {{ $user->street_address }}<br>
                    {{ $user->city }}, {{ optional($user->stateRelation)->name }} {{ $user->zip_code }}<br>
                    {{ $user->country }}


                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Social Security</p>

                <div class="relative flex items-center">
                    <p id="social_security" class="text-sm text-gray-900">
                        xxx-xx-xxx
                    </p>

                            <button type="button"
                data-open-verify
                data-field-id="social_security"
                data-verify-url="{{ route('admin.hrm.users.verify-ssn', $user->id) }}"
                class="ml-2 text-gray-500 hover:text-gray-700">
                <x-heroicon-o-eye class="w-5 h-5" />
            </button>

                </div>
            </div>



        </div>
    </div>

    <!-- Role Assignment -->
    <div class="bg-white p-5 rounded-lg border shadow">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-shield-check class="w-5 h-5 text-blue-600" />
            Role Assignment
        </h2>

        @foreach ($user->roles as $role)
        <!-- Role description -->
        <div class="bg-gray-50 border rounded-md px-4 py-3 flex items-start gap-3 mb-4">
            <div
                class="w-3 h-3 rounded-full mt-1"
                style="background-color: {{ $role->color }};"></div>
            <div>
                <p class="font-medium text-sm text-gray-900">{{ $role->name }}</p>
                <p class="text-sm text-gray-600">
                    {{ $role->description ?? 'No description provided.' }}
                </p>
            </div>
        </div>
        @endforeach

        <!-- Driver Designation -->
        <div class="border-t pt-4 mt-2">
            <div class="flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-2">
                    <input type="checkbox"
                        class="w-4 h-4 rounded border-gray-300 text-blue-600"
                        {{ $user->is_driver ? 'checked' : '' }}
                        disabled>
                    <span class="text-sm text-gray-700 font-medium">Designate as a Driver</span>
                </div>
                @if($user->cdl_a)
                <div class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600" checked disabled>
                    <span class="text-sm text-gray-700 font-medium">CDL A</span>
                </div>
                @endif
                @if($user->cdl_b)
                <div class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600" checked disabled>
                    <span class="text-sm text-gray-700 font-medium">CDL B</span>
                </div>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-1 ml-6">Drivers appear in the Dispatch driver assignment list.</p>
        </div>

    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">

    <!-- Employment Information Card -->
    <div class="bg-white p-6 rounded-lg border shadow">
        <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-4">
            <x-heroicon-o-briefcase class="w-5 h-5 text-blue-600" />
            Employment Information
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 text-sm text-gray-700">
            <div>
                <p class="text-xs text-gray-500 font-medium">Start Date</p>
                <p class="text-sm text-gray-900"> {{ \App\Helpers\CustomHelper::formatDate($user->start_date )  }} </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">End Date</p>
                <p class="text-sm text-gray-900">{{ \App\Helpers\CustomHelper::formatDate($user->end_date )  }} </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Status</p>
                <p class="text-sm text-gray-900">{{ $user->status }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Pay Type</p>
                <p class="text-sm text-gray-900">{{ $user->pay_type }}</p>
            </div>
        </div>
    </div>

    <!-- Time Clock Settings Card -->
    <div class="bg-white p-4 rounded-lg border shadow">
        <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-4">
            <x-heroicon-o-clock class="w-5 h-5 text-blue-600" />
            Time Clock Settings
        </h2>

        <div class="grid grid-cols-1 text-sm text-gray-700 gap-y-4">
            <!-- Clock Code -->
            <div>
                <p class="text-xs text-gray-500 font-medium">Employee Code</p>
                <p class="text-sm text-gray-900">{{ $user->employee_code ?? '' }}</p>
            </div>
            <!-- Limit Times side-by-side -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:gap-6 ">
                <!-- Limit Start Time -->
                <div class="flex-1">
                    <p class="text-xs text-gray-500 font-medium">Limit Start Time</p>
                    <p class="text-sm text-gray-900"> {{ $user->limit_start_time == 1 ? 'Yes' : 'No' }}</p>
                </div>

                <!-- Limit End Time -->
                <div class="flex-1 mt-4 md:mt-0 lg-mt-0">
                    <p class="text-xs text-gray-500 font-medium">Limit End Time</p>
                    <p class="text-sm text-gray-900"> {{ $user->limit_end_time == 1 ? 'Yes' : 'No' }} </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal (hidden by default) --}}
<div id="verify-modal" class="fixed inset-0 z-[99999] hidden" role="dialog" aria-modal="true"
    aria-labelledby="verify-title">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40"></div>

    <!-- Panel -->
    <div class="relative mx-auto my-10 max-w-lg w-[92%]">
        <div class="bg-white rounded-xl shadow-xl border border-gray-200">
            <div class="px-5 pt-4 pb-2 flex items-start justify-between">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-lock-closed class="w-5 h-5 text-red-500" />
                    <h3 id="verify-title" class="text-lg font-semibold text-gray-900">Security Verification Required
                    </h3>
                </div>
                <button type="button" class="p-1 text-gray-400 hover:text-gray-600" data-modal-close
                    aria-label="Close">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            <div class="px-5 pb-4">
                <p class="text-sm text-gray-600 mb-3">Enter your Master Password to edit the Admin Code.</p>

                <div class="relative">
                    <input id="verify-password" type="password" autocomplete="current-password"
                        class="w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 border-gray-300"
                        placeholder="Enter your Master Password">
                    <!-- eye -->
                    <button type="button"
                        class="absolute inset-y-0 right-0 w-10 grid place-items-center text-gray-400 hover:text-gray-600"
                        data-toggle="visibility" data-target="verify-password" aria-label="Show password">
                        <x-heroicon-o-eye data-eye class="w-5 h-5" />
                        <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                    </button>
                </div>

                <p id="verify-error" class="text-sm text-red-600 mt-2 hidden"></p>

                <div class="mt-4 flex items-center gap-3">
                    <button type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-indigo-500 text-white hover:bg-indigo-600 disabled:opacity-60"
                        id="verify-submit">
                        <x-heroicon-o-lock-closed class="w-4 h-4" />
                        Verify & Edit
                    </button>
                    <button type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                        data-modal-close>
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {

        // password visibility (delegated)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-toggle="visibility"]');
            if (!btn) return;
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            // Only allow visibility toggle if input is NOT disabled (i.e., verified)
            if (input.hasAttribute('disabled')) {
                // If not verified, open modal for verification
                if (input.id === 'verify-password') return; // Don't open modal for the modal's own input
                // Find related lock button and trigger modal
                const opener = document.querySelector(`[data-open-verify][data-field-id="${input.id}"]`);
                if (opener) opener.click();
                return;
            }

            const eye = btn.querySelector('[data-eye]');
            const eyeOff = btn.querySelector('[data-eye-off]');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            if (eye) eye.classList.toggle('hidden', !isPassword);
            if (eyeOff) eyeOff.classList.toggle('hidden', isPassword);
            btn.setAttribute('aria-label', isPassword ? 'Hide value' : 'Show value');
            btn.setAttribute('aria-pressed', String(isPassword));
        });

        const modal = document.getElementById('verify-modal');
        const verifyInput = document.getElementById('verify-password');
        const verifyError = document.getElementById('verify-error');
        const verifyBtn = document.getElementById('verify-submit');

        let targetFieldId = null;
        let verifyUrl = null;


        // open modal from lock buttons
        document.addEventListener('click', function(e) {
            const opener = e.target.closest('[data-open-verify]');
            if (!opener) return;

            targetFieldId = opener.getAttribute('data-field-id');

                verifyUrl = opener.getAttribute('data-verify-url');


           const field = document.getElementById(targetFieldId);

        // Only block if it is an INPUT and already enabled
        if (field && field.tagName !== 'P' && !field.hasAttribute('disabled')) return;


            openModal();
        });

        // close modal (X or Cancel or backdrop)
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-modal-close]') || e.target.closest('[data-modal-close]')) {
                closeModal();
            }
            if (e.target === modal) { // click backdrop
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        function openModal() {
            verifyInput.value = '';
            verifyError.textContent = '';
            verifyError.classList.add('hidden');
            modal.classList.remove('hidden');
            setTimeout(() => verifyInput.focus(), 0);
        }

        function closeModal() {
            modal.classList.add('hidden');
        }





    // Submit verification
    verifyBtn.addEventListener('click', async function () {

        if (!verifyUrl) return;

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const response = await fetch(verifyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                password: verifyInput.value,
                field_name: targetFieldId
            })
        });

        const data = await response.json();

        if (data.success) {
            const field = document.getElementById(targetFieldId);

            if (field.tagName === 'P') {
                field.textContent = data.field_value || data.ssn;
            } else {
                field.value = data.field_value || '';
                field.removeAttribute('disabled');
                field.type = 'text';
            }

            modal.classList.add('hidden');
        } else {
            verifyError.textContent = data.message;
            verifyError.classList.remove('hidden');
        }
    });


});
</script>
@endpush


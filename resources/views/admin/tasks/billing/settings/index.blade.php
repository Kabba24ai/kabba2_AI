@extends('admin.layouts.app')

@section('title', 'Billing Operations Settings')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Task Manager → Billing Operations → Settings.
         The Primary Billing Admin is the single canonical employee future
         collection/AI workflows will consult. Stored as one settings row
         (a users.id); the dropdown offers active employees only. --}}
    <div class="max-w-2xl mx-auto px-4 py-6">

        <div class="mb-5">
            <h1 class="text-xl font-bold text-gray-900">Billing Operations Settings</h1>
            <p class="text-sm text-gray-500 mt-0.5">Designate the employee responsible for billing resolution.</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <form action="{{ route('admin.tasks.billing.settings.save') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="primary_billing_admin_id" class="block text-sm font-semibold text-gray-800 mb-1">
                        Primary Billing Admin
                    </label>
                    <p class="text-xs text-gray-500 mb-2">
                        The single canonical designation used by billing resolution and future collection workflows.
                        Only active employees can be selected.
                    </p>
                    <select name="primary_billing_admin_id" id="primary_billing_admin_id"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">— Not designated —</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) $currentId === (int) $employee->id)>
                                {{ $employee->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('primary_billing_admin_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($current)
                    <p class="text-sm text-gray-600">
                        Current: <span class="font-semibold text-gray-900">{{ $current->full_name }}</span>
                    </p>
                @elseif ($currentId)
                    <p class="text-sm text-amber-600">
                        The previously designated employee is no longer active — please select a current employee.
                    </p>
                @else
                    <p class="text-sm text-gray-500">No Primary Billing Admin is currently designated.</p>
                @endif

                <div class="pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

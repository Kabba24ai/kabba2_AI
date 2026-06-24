@extends('admin.layouts.app')

@section('title', 'Create Task')

@section('content')

@include('flash::message')
@include('admin.partials.formErrors')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.tasks.index') }}" class="text-gray-500 hover:text-gray-700">
        <x-heroicon-o-arrow-left class="w-5 h-5" />
    </a>
    <h3 class="text-xl font-semibold text-gray-800">Create Task</h3>
</div>

<div class="max-w-2xl bg-white rounded-lg border border-gray-200 shadow-sm p-6">
    <form method="POST" action="{{ route('admin.tasks.store') }}">
        @csrf

        {{-- Category --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
            <select name="category" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                <option value="">Select Category</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->value }}" {{ old('category') === $cat->value ? 'selected' : '' }}>{{ $cat->label() }}</option>
                @endforeach
            </select>
        </div>

        {{-- Title --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title') }}" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                placeholder="What needs to be done?">
        </div>

        {{-- Description --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                placeholder="Optional details...">{{ old('description') }}</textarea>
        </div>

        {{-- Priority + Status --}}
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority <span class="text-red-500">*</span></label>
                <select name="priority" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    @foreach ($priorities as $pri)
                        <option value="{{ $pri->value }}" {{ old('priority', 'normal') === $pri->value ? 'selected' : '' }}>{{ $pri->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    @foreach ($statuses as $st)
                        <option value="{{ $st->value }}" {{ old('status', 'open') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Assigned To + Due Date --}}
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assign To</label>
                <select name="assigned_to_user_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    <option value="">Unassigned</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ old('assigned_to_user_id') == $user->id ? 'selected' : '' }}>{{ $user->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                <input type="datetime-local" name="due_date" value="{{ old('due_date') }}"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="inline-flex items-center rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white shadow hover:bg-brand-600">
                Create Task
            </button>
            <a href="{{ route('admin.tasks.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
        </div>

    </form>
</div>

@endsection

@extends('admin.layouts.app')

@section('title', 'Edit Demo Fields')

@push('css')
@endpush

@section('content')
    @include('flash::message')

    <div class="bg-gray-50 px-4 py-4 border-b border-gray-200 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <a href="{{ route('admin.crm.kabba-ai-customers.show', $submission->unique_id) }}"
                   class="flex items-center text-gray-600 hover:text-gray-800 transition">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-1" />
                    <span class="text-sm font-medium">Back to Details</span>
                </a>

                <div class="hidden sm:block h-6 border-l border-gray-300"></div>

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Demo Fields</h1>
                    <p class="text-sm text-gray-500">
                        {{ trim(($submission->first_name ?? '') . ' ' . ($submission->last_name ?? '')) ?: 'Unknown' }}
                        ({{ $submission->unique_id }})
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto">
        <form action="{{ route('admin.crm.kabba-ai-customers.update', $submission->unique_id) }}" method="POST"
              class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                    <select id="status" name="status"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @php $selectedStatus = old('status', $submission->status ?? 'pending'); @endphp
                        <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ $selectedStatus === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div> --}}

                <div>
                    <label for="setup_status" class="block text-sm font-medium text-gray-700 mb-2">Setup Status</label>
                    <select id="setup_status" name="setup_status"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @php $selectedSetup = old('setup_status', $submission->setup_status ?? 'pending'); @endphp
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
                <textarea id="comment" name="comment" rows="8" placeholder="Write long comment here..."
                          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('comment', $submission->comment ?? '') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">You can add detailed notes, updates, and follow-up comments here.</p>
                @error('comment')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-8 flex items-center justify-end gap-3">
                <a href="{{ route('admin.crm.kabba-ai-customers.show', $submission->unique_id) }}"
                   class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center px-5 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection

@push('js')
@endpush

@extends('admin.layouts.app')

@section('title', 'Edit ' . $ticket->ticket_number)

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold flex items-center gap-2">
            <x-heroicon-o-pencil-square class="w-6 h-6 text-blue-600" />
            Edit {{ $ticket->ticket_number }}
        </h1>
        <a href="{{ route('admin.service-management.tickets.show', $ticket) }}"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            Back to Ticket
        </a>
    </div>

    <form method="POST" action="{{ route('admin.service-management.tickets.update', $ticket) }}">
        @csrf
        @method('PUT')
        @include('admin.service_management.tickets.partials._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.service-management.tickets.show', $ticket) }}"
                class="px-6 py-3 rounded-lg font-medium text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-3 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                Save Changes
            </button>
        </div>
    </form>

@endsection

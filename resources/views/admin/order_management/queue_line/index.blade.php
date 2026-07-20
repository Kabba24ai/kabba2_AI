@extends('admin.layouts.app')

@section('title', 'Queue Line')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Queue Line</h1>
        <p class="text-sm text-gray-500 mt-1">
            Outbound equipment due through tomorrow — stage it, rush it, and keep customers and drivers moving.
        </p>
    </div>

    <livewire:queue-line.board />
@endsection

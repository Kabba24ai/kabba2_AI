@extends('admin.layouts.app')

@section('title', 'Queue Line')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Queue Line</h1>
        <p class="text-sm text-gray-500 mt-1">
            What machine do I need to pull for this order? Confirm the equipment, verify fuel, and it moves to Staged.
        </p>
    </div>

    <livewire:queue-line.board />
@endsection

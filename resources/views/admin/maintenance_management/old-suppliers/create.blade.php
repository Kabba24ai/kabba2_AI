@extends('admin.layouts.app')

@section('title', 'Add New Supplier')

@section('content')
<div class="min-h-screen bg-gray-50">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.maintenance-management.suppliers.index') }}" 
                   class="text-gray-600 hover:text-gray-900 p-2 hover:bg-gray-100 rounded-lg">
                    ←
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <svg class="h-7 w-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Add New Supplier
                    </h1>
                    <p class="text-gray-600 mt-1">Enter supplier information to add them to your database</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="px-6 py-6">
        <form method="POST" action="{{ route('admin.maintenance-management.suppliers.create') }}" class="max-w-6xl mx-auto">
            @csrf
            @include('admin.maintenance_management.suppliers.partials._form')
        </form>
    </div>
</div>
@endsection

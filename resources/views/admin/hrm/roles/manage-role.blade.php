@extends('admin.layouts.app')

@section('title', 'Role Management')

@section('content')

    @include('flash::message')

    <div class="border-b pb-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <!-- Left: Title and Subtitle -->
            <div class="flex items-start sm:items-center gap-3">
                <div class="text-blue-600">
                    <x-heroicon-o-shield-check class="w-6 h-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Role Management</h1>
                    <p class="text-sm text-gray-600">Manage roles and permissions</p>
                </div>
            </div>

           
            <div class="flex flex-col sm:flex-row flex-wrap items-center justify-end gap-2">
                <!-- Back link -->
                <a href="{{ route('admin.hrm.users.index') }}"
                    class="text-sm font-medium text-gray-600 hover:text-gray-800">
                    Back to Employees
                </a>
                
                <!-- Add Role Button -->
                <a href="{{ route('admin.hrm.roles.create.role') }}"
                    class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium flex items-center justify-center gap-1 hover:bg-blue-700 transition">
                    <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add Role
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
        @foreach($roles as $role)
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="max-w-xl mx-auto mb-6">
                    <!-- Role Header Row -->
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-full" style="background-color: {{ $role->color }}20;">
                                <svg class="w-5 h-5" style="color: {{ $role->color }}" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 3l8.485 4.243A2 2 0 0121 8.944V13a9 9 0 01-18 0V8.944a2 2 0 01.515-1.701L12 3z" />
                                </svg>
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">{{ $role->name }}</h3>
                                <div class="flex items-center text-xs text-gray-500 gap-1 mt-0.5">
                                    <x-heroicon-o-users class="w-3 h-3 text-gray-500" />
                                    <span>{{ $role->users_count }} user{{ $role->users_count !== 1 ? 's' : '' }} •</span>
                                    <span>{{ $role->permissions_count }} permissions</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Edit/Delete Icons -->
                        <div class="flex gap-2 text-gray-500">
                            <a href="{{ route('admin.hrm.roles.edit.role',$role->unique_id) }}" title="Edit">
                                <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-600" />
                            </a>
                            <button class="delete-role-btn" data-url="{{ route('admin.hrm.roles.delete', $role->unique_id) }}"
                                            type="button" title="Delete">
                                <x-heroicon-o-trash class="w-4 h-4 text-gray-600" />
                            </button>
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-700 mb-4">
                    {{ $role->description ?? 'No description available' }}
                </p>

                <span class="inline-block text-xs px-3 py-1 rounded-full font-medium"
                    style="background-color: {{ $role->color }}20; color: {{ $role->color }};">
                    {{ $role->short_name ?? $role->name }}
                </span>
            </div>
        @endforeach
    </div>

     

@endsection


@push('js')

 <!-- delete user script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteButtons = document.querySelectorAll('.delete-role-btn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const url = this.dataset.url;

                    window.showConfirm(
                        'Are you sure you want to delete this Role ? This action cannot be undone.',
                        'Delete Role' 
                    ).then((result) => {
                        if (result.isConfirmed) {
                            fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    notyf.success(data.message || 'Role deleted.');
                                    // Reload or redirect
                                    window.location.reload();
                                } else {
                                    notyf.error(data.message || 'Could not delete role .');
                                }
                            })
                            .catch(error => {
                                console.error(error);
                                notyf.error('Something went wrong.');
                            });
                        }
                    });
                });
            });
        });
    </script>
        <!-- delete user script --> 


@endpush
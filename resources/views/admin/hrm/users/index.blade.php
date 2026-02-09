@extends('admin.layouts.app')

@section('title', 'Roles List')

@section('content')

    @include('flash::message')

    <div class="">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user class="w-6 h-6 text-blue-600" />
                    <div>
                        <h2 class="text-base sm:text-lg font-semibold text-gray-900">User Account Settings</h2>
                        <p class="text-sm text-gray-600">Manage employee information and settings</p>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons -->
            <div class="flex flex-wrap justify-right items-center gap-2">
                <a href="{{route('admin.hrm.roles.manage')}}" class="bg-gray-700 text-white px-4 py-2 rounded flex items-center gap-2 text-sm font-medium">
                    <x-heroicon-o-shield-check class="w-5 h-5" />
                    Manage Roles
                </a>
                <a href="{{route('admin.hrm.users.create')}}" class="bg-blue-600 text-white px-4 py-2 rounded flex items-center gap-2 text-sm font-medium">
                    <x-heroicon-o-plus class="w-5 h-5" />
                    Add Employee
                </a>
            </div>
        </div>
    </div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
    <div class="container">
        <div class="bg-white border border-gray-200 rounded-md p-4 w-full">
            <div class="flex justify-left">
                <ul class="flex flex-wrap gap-2 text-sm sm:text-base font-medium">

                    <li id="filter__Roles" data-category="all" class="cursor-pointer text-sm px-4 py-2 bg-blue-600 text-white  rounded ">All Roles</li>

                    @foreach ($roles as $role)
                        <li
                            id="filter__{{ $role->short_name }}"
                            data-category="{{ $role->short_name }}"
                            class="cursor-pointer text-sm px-4 py-2 rounded"
                            style="background-color: {{ $role->color }}20; color: {{ $role->color }};"
                        >
                            {{ $role->name }}
                        </li>
                    @endforeach 

                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white border rounded-md p-4 w-full mt-6">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
            
            <!-- Search Field -->
            <div class="flex items-center w-full sm:w-full border rounded-md px-3 py-2 text-sm shadow-sm border-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 5.64 5.64a7.5 7.5 0 0 0 10.61 10.61z" />
                </svg>
                <input
                    type="text" value="{{ request('search_name') }}"  name="search_name"
                    placeholder="Search employees by name or email..."
                    class="w-full outline-none text-sm bg-transparent text-gray-700 placeholder-gray-400"
                />
            </div>

            <!-- Status Dropdown -->
          <div class="w-full sm:w-48 relative">
            <label for="user_status" class="sr-only">Status</label>
                <div class="flex items-center border border-gray-300 rounded-md shadow-sm px-3 py-2 bg-white text-sm text-gray-700">
                    <!-- Filter Icon -->
                    <x-heroicon-o-funnel class="w-5 h-5 text-gray-500" />
                    <!-- Dropdown -->
                    <select  id="user_status" name="user_status" class="w-full bg-transparent outline-none text-gray-700 pr-4">
                        <option value="all">All Status</option>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
          </div>

            
        </div>
    </div>
</div>

    
    <div class="mt-6" id="user-wrapper">
        @include('admin.hrm.users.partials._user_cards')
    </div>

@endsection

@push('js')
    <script>
        let selectedCategory = 'all'; // default
    </script>
        <!-- delete user script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteButtons = document.querySelectorAll('.delete-user-btn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const url = this.dataset.url;

                    window.showConfirm(
                        'Are you sure you want to delete this user? This action cannot be undone.',
                        'Delete User'
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
                                    notyf.success(data.message || 'User deleted.');
                                    // Reload or redirect
                                    window.location.reload();
                                } else {
                                    notyf.error(data.message || 'Could not delete user.');
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
    <script>
        function setupTabFiltering() {
            const filterButtons = document.querySelectorAll('ul li[id^="filter__"]');
            const cards = document.querySelectorAll('.tv-filter-item');

            filterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    selectedCategory = button.getAttribute('data-category') || 'all'; // track selection
                    applyCategoryFilter(); // apply filtering to cards
                    updateTabStyles(button, filterButtons);
                });
            });

            // Store original styles only once
            filterButtons.forEach(btn => {
                if (!btn.hasAttribute('data-original-bg')) {
                    const bgColor = btn.style.backgroundColor;
                    const textColor = btn.style.color;
                    btn.setAttribute('data-original-bg', bgColor);
                    btn.setAttribute('data-original-text', textColor);
                }
            });
        }
    </script>
        <!-- tab filter  -->
        <!-- search cilter script  -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const nameInput = document.querySelector('input[name="search_name"]');
            const statusSelect = document.querySelector('select[name="user_status"]');
            const wrapper = document.querySelector('#user-wrapper');

            let timeout = null;

        function fetchUsers() {
            const name = nameInput.value.trim();
            const user_status = statusSelect.value;

            const params = new URLSearchParams();
            if (name.length >= 3 || name.length === 0) params.append('search_name', name);
            if (user_status.toLowerCase() !== 'all') params.append('user_status', user_status);

            wrapper.classList.add('opacity-50', 'pointer-events-none');

            fetch("{{ route('admin.hrm.users.index') }}?" + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                document.querySelector('#user-wrapper').innerHTML = data.html;

                setupTabFiltering();       
                applyCategoryFilter();     
            })
            .catch(err => {
                wrapper.innerHTML = '<div class="text-red-500 p-4">Error loading users.</div>';
                console.error(err);
            })
            .finally(() => {
                wrapper.classList.remove('opacity-50', 'pointer-events-none');
            });
        }
            nameInput.addEventListener('input', function () {
                clearTimeout(timeout);
                timeout = setTimeout(fetchUsers, 400);
            });
            statusSelect.addEventListener('change', fetchUsers);
            setupTabFiltering();

             fetchUsers();
        });




        function updateTabStyles(activeButton, filterButtons) {
            filterButtons.forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'text-gray-600');

                const originalBg = btn.getAttribute('data-original-bg');
                const originalText = btn.getAttribute('data-original-text');

                if (originalBg && originalText) {
                    btn.style.backgroundColor = originalBg;
                    btn.style.color = originalText;
                }

                btn.classList.add('text-gray-600');
            });

            activeButton.classList.remove('text-gray-600');
            activeButton.classList.add('bg-blue-600', 'text-white');
            activeButton.style.backgroundColor = '';
            activeButton.style.color = '';
        }

        function applyCategoryFilter() {
            const cards = document.querySelectorAll('.tv-filter-item');

            cards.forEach(card => {
                const cardCategory = card.getAttribute('data-category');
                if (
                    selectedCategory === 'all' ||
                    (cardCategory && cardCategory.split(' ').includes(selectedCategory))
                ) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });

            const anyVisible = Array.from(cards).some(card => !card.classList.contains('hidden'));
            document.getElementById('no-users-message')?.classList.toggle('hidden', anyVisible);
        }


    </script>
        <!-- search cilter script  -->
@endpush



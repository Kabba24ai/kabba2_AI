
<div class="w-full max-w-md sm:max-w-lg md:max-w-2xl lg:max-w-4xl xl:max-w-4xl mx-auto">
    <div  id="cardWrapper" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-3"> 
            @foreach ($users as $user)
            
            @php
                $userCategories = implode(' ', $user->role_short_names); // space-separated for multiple filters
            @endphp

                <!-- Card 1 -->
                <div class="tv-filter-item tv-case-study tv-case-study-show" data-category="{{ $userCategories }}">
                    <div class="bg-gray-50">
                        <div class="">
                            <div class="bg-white rounded-md shadow border p-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex gap-3 items-start">
                                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                            <x-heroicon-o-user class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <h2 class="text-base font-semibold text-gray-800">{{ $user->first_name }} {{ $user->middle_name }}  {{ $user->last_name }}  </h2>
                                            <div class="flex gap-2 mt-1 text-xs">
                                                {{-- Status Badge --}}
                                                @if($user->status === 'Active')
                                                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full">active</span>
                                                @elseif($user->status === 'Inactive')
                                                    <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded-full">inactive</span>
                                                @endif
                                                
                                                <!-- {{-- Pay Type Badge --}} -->
                                                @if($user->pay_type && isset($paytypes[$user->pay_type]))
                                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">
                                                        {{ $paytypes[$user->pay_type] }}
                                                    </span>
                                                @endif

                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.hrm.users.view',$user->unique_id ) }}"><x-heroicon-o-eye class="w-4 h-4 text-grey-600" /></a>
                                        <a href="{{ route('admin.hrm.users.edit',$user->unique_id ) }}" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </a>
                                    <!-- Delete Button -->
                                
                                        <button 
                                            class="delete-user-btn"
                                            data-url="{{ route('admin.hrm.users.delete', $user->unique_id) }}"
                                            type="button"
                                            title="Delete"
                                        >
                                            <x-heroicon-o-trash class="w-4 h-4 text-red-600" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="mt-4 text-sm border-b text-gray-700 space-y-2 mb-4">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                                        {{ $user->email }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                        {{ \App\Helpers\CustomHelper::formatPhone($user->mobile_phone ?? '') ?: ' ' }} <span class="text-gray-400 mx-1">-</span> <span class="text-gray-400">{{ \App\Helpers\CustomHelper::formatPhone($user->phone_number ?? '') ?: ' ' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                                    {{  $user->street_address }} {{ $user->city }} {{  $user->state }}   {{  $user->zip_code }}  {{  $user->country }}     
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-briefcase class="w-4 h-4 text-gray-900" />
                                        Employee Code: {{  $user->employee_code ?? 'N/A' }} 
                                    </div>
                                    <div class="flex items-center gap-2 mb-4">
                                    <x-heroicon-o-calendar class="w-4 h-4 text-gray-900" />
                                        Started: {{  \App\Helpers\CustomHelper::formatDate($user->start_date )  }}
                                    </div>
                                </div>

                                <!-- Roles -->
                                <div class="mt-4  flex gap-2 flex-wrap">
                                    @foreach($user->roles as $role)
                                    <span
                                        class="text-xs px-3 py-1 rounded-full"
                                        style="background-color: {{ $role->color }}20; color: {{ $role->color }};"
                                    >
                                    <strong> {{ $role->name }} </strong>
                                    </span>
                                    @endforeach
                                    <!-- <span class="bg-orange-100 text-orange-800 text-xs px-3 py-1 rounded-full">Manager</span> -->
                                </div>

                                <!-- Emergency Contact 1 -->
                                @if($user->emergencyContactOne)
                                    <div class="mt-4 pt-2 border-t text-sm text-gray-700">
                                        <p class="font-semibold mb-1">Emergency Contact 1</p>
                                        <p>{{ $user->emergencyContactOne->first_name }} {{ $user->emergencyContactOne->last_name }}</p>

                                        @if($user->emergencyContactOne->phone_number)
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                                {{ $user->emergencyContactOne->phone_number }}
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Emergency Contact 2 -->
                                @if($user->emergencyContactTwo)
                                    <div class="mt-4 pt-2 border-t text-sm text-gray-700">
                                        <p class="font-semibold mb-1">Emergency Contact 2</p>
                                        <p>{{ $user->emergencyContactTwo->first_name }} {{ $user->emergencyContactTwo->last_name }}</p>

                                        @if($user->emergencyContactTwo->phone_number)
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                                {{ $user->emergencyContactTwo->phone_number }}
                                            </div>
                                        @endif
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>

            @endforeach            

        </div>

        <p id="no-users-message" class="hidden text-center text-gray-600 text-sm mt-4">
            No users are available.
        </p>
    </div>
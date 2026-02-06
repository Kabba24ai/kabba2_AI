@extends('admin.layouts.app')

@section('title', 'Edit Checklist Master')

@push('css')

@endpush

@section('content')

@include('flash::message')
@include('admin.partials.formErrors')

<div class="bg-white  flex flex-col md:flex-row items-start md:items-center justify-between w-full border-b border-gray-200 px-2 py-2">
    <!-- Left Side: Icon, Title, Description -->
    <div class="flex items-start gap-3">
        <x-heroicon-o-document class="w-8 h-8 text-blue-600" />
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Edit Checklist Master System <span class="checklist-title-display"> - </span> </h2>
            <p class="text-sm text-gray-600" id="stepSubtitle">Update the name of your checklist system</p>

        </div>
    </div>

    <!-- Right Side: Cancel Button -->
    <div class="mt-4 md:mt-0">
        <a href="{{ route('admin.checklist-management.checklist-master.index') }}" class="flex items-center text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Cancel
        </a>
    </div>
</div>

<!-- Step Header (Hidden initially and shown on step 2+) -->
<div id="stepHeader" class="mb-6 hidden-step-header mx-auto px-4 py-4 border-b border-gray-200">
    <div class="flex flex-col md:flex-row md:flex-wrap md:justify-between gap-4 text-sm text-gray-700">

        <!-- Step 1 -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px]" id="step-1-redirect">
            <div id="stepIcon1" class="w-8 h-8 rounded-full border border-gray-300 text-gray-600 flex items-center justify-center font-bold">1</div>
            <div>
                <p class="font-semibold">Name Your Checklist Master</p>
                <p class="text-xs text-gray-500">Create and name your checklist master system</p>
            </div>
        </div>

        <!-- Arrow -->
        <div class="hidden md:block text-gray-300 self-center">➝</div>

        <!-- Step 2 -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px]" id="step-2-redirect">
            <div id="stepIcon2" class="w-8 h-8 rounded-full border border-gray-300 text-gray-600 flex items-center justify-center font-bold">2</div>
            <div>
                <p class="font-semibold">Assign Rental Ready Template</p>
                <p class="text-xs text-gray-500">Select rental ready checklist master template</p>
            </div>
        </div>

        <!-- Arrow -->
        <div class="hidden md:block text-gray-300 self-center">➝</div>

        <!-- Step 3 -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px]" id="step-3-redirect">
            <div id="stepIcon3" class="w-8 h-8 rounded-full border border-gray-300 text-gray-600 flex items-center justify-center font-bold">3</div>
            <div>
                <p class="font-semibold">Assign Customer Template</p>
                <p class="text-xs text-gray-500">Select customer checklist master template</p>
            </div>
        </div>

        <!-- Arrow -->
        <div class="hidden md:block text-gray-300 self-center">➝</div>

        <!-- Step 4 -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px]" id="step-4-redirect">
            <div id="stepIcon4" class="w-8 h-8 rounded-full border border-gray-300 text-gray-600 flex items-center justify-center font-bold">4</div>
            <div>
                <p class="font-semibold">Complete & Save</p>
                <p class="text-xs text-gray-500">Save and return to Equipment Mgt.</p>
            </div>
        </div>
    </div>
</div>


<!-- Step Container -->
<!-- <section class="max-w-4xl mx-auto mt-8 p-6 bg-white rounded shadow"> -->
<section class="">

    <!-- Step 1 -->
    <div id="step1" class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow space-y-6 mt-6">
        <div>
            <!-- Header Icon and Title -->
            <div class="flex flex-col items-center text-center space-y-1 mb-8">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list w-16 h-16 text-blue-600 mx-auto mb-4">
                    <rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect>
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                    <path d="M12 11h4"></path>
                    <path d="M12 16h4"></path>
                    <path d="M8 11h.01"></path>
                    <path d="M8 16h.01"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-900">Edit System Name</h2>
                <p class="text-gray-600 text-sm">Update the name of this checklist master system. This change will be reflected
                    wherever this system is used.</p>
            </div>



           <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
             <!-- Checklist System Name -->
          
            <div class="space-y-1">
                <label for="systemName" class="text-sm font-medium text-gray-700 mb-1">Checklist Master System Name <span class="text-red-500">*</span></label>
                <input id="systemName" oninput="toggleContinue()" value="{{ $checklistmaster->checklist_system_name }}" type="text" placeholder="e.g., Standard Heavy Equipment System" class="w-full border px-3 py-2 rounded-md text-sm text-sm border-gray-300" />
                <p class="text-sm text-gray-500 mt-2 ">Choose a descriptive name that identifies this checklist system. </p>
            </div>
            <!-- Equipment Category -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700 block">
                  Assign To Equipment Category <span class="text-red-500">*</span>
                </label>

               {!! html()->select(
                    'checklist_category',
                    ['' => '--Select Category--'] + $equipmentCategories,
                    old('checklist_category', $checklistmaster->equipment_category_id ?? '')
                )
                ->id('checklist_category')
                ->class('w-full border px-3 py-2 rounded-md text-sm border-gray-300')
                ->attribute('onchange', 'storeCategoryId(this)')
                !!}

                
              
            </div>
        </div>

            <!-- System Information -->
            <div class="bg-blue-50 border border-blue-100 rounded-md p-4 mb-6">
                <h4 class="text-sm font-semibold text-blue-900 mb-2">System Information</h4>
                <ul class="text-sm text-blue-900 list-decimal list-inside space-y-1">
                    <li> <strong>System ID:</strong> {{ $checklistmaster->unique_id }}</li>
                    <li> <strong>Created: </strong> {{ \App\Helpers\CustomHelper::formatDate($checklistmaster->created_at ?? null)  }}</li>
                    <li> <strong>Last Updated:</strong> {{ \App\Helpers\CustomHelper::formatDate($checklistmaster->updated_at ?? null)  }} </li>
                    <li> <strong>Status: </strong> Active </li>

                </ul>
            </div>

            
            <div class="flex items-center justify-center gap-6 pt-6 pb-6 w-full max-w-3xl mx-auto">
                <!-- Left group: Save + Edit buttons centered -->
                <div class="flex flex-1 justify-center gap-4">
                    <button id="savethetemp"
                        class="flex items-center gap-2 px-6 py-2 bg-blue-600 rounded-md text-white justify-center text-sm font-medium flex-1 max-w-[180px]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-save w-4 h-4">
                            <path
                                d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z">
                            </path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Save Changes
                    </button>

                    <button id="continueBtn" onclick="goToStep(2)" disabled
                        class="flex items-center gap-2 px-6 py-2 bg-green-600 rounded-md text-gray-500 cursor-not-allowed justify-center text-sm font-medium flex-1 max-w-[180px]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-save w-4 h-4">
                            <path
                                d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z">
                            </path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Edit Checklist
                    </button>
                </div>

                <!-- Cancel link aligned right -->
                <a href="{{ route('admin.checklist-management.checklist-master.index') }}"
                    class="text-sm text-gray-600 hover:underline whitespace-nowrap">
                    Cancel
                </a>
            </div>

            <!-- System Assignment -->
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4 flex items-start gap-3 mb-6">
                <x-heroicon-o-document-text class="w-4 h-6 sm:w-6 sm:h-6 md:w-4 md:h-6 text-gray-900 flex-shrink-0" />
                <div>
                    <p class="font-semibold text-gray-700">Checklist Master System Assignment</p>
                    <p class="text-sm text-gray-600">This checklist master system can be assigned to multiple equipment items through the
                        Equipment Profile screen. Changing the name will update it everywhere this
                        system is used.</p>
                </div>
            </div>


        </div>
    </div>

    <!-- Step 2 -->
    <div id="step2" class="hidden max-w-7xl mx-auto bg-white p-6 rounded-lg shadow space-y-6 mt-6">

        <!-- Step Title -->
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm p-4 rounded-md mb-6">
            <strong class="block font-medium">Step 2: Assign Rental Ready Template</strong>
            <p class="mt-1">Select an existing rental ready template or create a new one. This template will be assigned to your checklist system for equipment inspections.</p>
        </div>

        <div class="flex items-center justify-between mb-5">
            <h3 class="text-md font-semibold text-gray-800">Select Rental Ready Template</h3>
        </div>
        <!-- Filters -->
        <div class="grid md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block">Search Templates</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input id="templateSearch" type="text" placeholder="Search rental ready templates..." class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-md" value="">
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block">Equipment Category</label>
                {!! html()->select(
                'equipment_category',
                ['' => 'All Categories'] + $equipmentCategories,
                old('equipment_category')
                )
                ->class('w-full border px-3 py-2 rounded-md text-sm')
                !!}


            </div>
        </div>

        <!-- Card Options as Radio Buttons -->
        <!-- Card Options as Radio Buttons -->
        <form id="templateForm" class="space-y-4 grid md:grid-cols-2 gap-4">
            @foreach ($checklisttemplate as $template)
            <input type="radio" name="template"
                id="template_{{ $template->id }}"
                class="card-radio-step hidden"
                name="template"
                value="{{ $template->template_name }}"
                data-id="{{ $template->id }}"

                data-category-id="{{ $template->equipment_category_id }}"

                @checked($checklistmaster->rental_ready_template_id == $template->id)
            >

            <label for="template_{{ $template->id }}"
                class="template-card flex justify-between mb-0 gap-4 border border-gray-200 rounded-lg p-4 cursor-pointer transition-all">

                <!-- Left section: icon and details -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-top gap-3">
                        <!-- Icon -->
                        <x-heroicon-o-document class="w-6 h-6 text-green-600" />
                        <div>
                            <p class="font-semibold text-gray-900 leading-tight template-name">{{ $template->template_name }}</p>
                            <p class="text-sm text-gray-600">{{ $template->equipmentCategory->getHierarchyLabel() ?? 'No Category' }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ $template->description ?? 'No description' }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $template->questions->count() }}
                        {{ Str::plural('question', $template->questions->count()) }}
                    </p>
                </div>

                <!-- Right section: checkmark + status -->
                <div class="flex flex-col justify-between items-end">
                    <svg class="checkmark w-6 h-6 text-green-600"
                        fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>

                    <span class="text-xs px-3 py-1 rounded-full mt-auto
                             {{ $template->active_template ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $template->active_template ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </label>
            @endforeach
        </form>


        <!-- Template Selected Box -->
        <div id="templateSummary" class="hidden mt-6 bg-green-50 border border-green-200 text-green-800 p-4 rounded-md text-sm">
            <div class="flex items-center gap-2 font-semibold mb-2 flex-wrap">
                <!-- Check icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>

                <!-- Inline label and dynamic value -->
                <span>Template Selected:</span>
                <span id="selectedTemplate">-</span>
            </div>

            <p class="text-sm text-green-900 leading-snug">
                This template will be assigned to your checklist system for rental ready inspections.
            </p>
        </div>


        <!-- Action Buttons -->
        <div class="flex flex-col md:flex-row justify-between mt-8 gap-4">
            <a href="{{ route('admin.checklist-management.rental-ready.index') }}" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">Cancel New Checklist and Create Rental Ready Template</a>


            <button id="continueStep2Btn" onclick="validateStep2BeforeContinue()"

                class=" bg-green-600 text-white hover:bg-green-700 cursor-pointer px-4 py-2 rounded-md text-sm font-medium md:w-auto flex items-center gap-2">Continue to Customer Template
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-4 h-4">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                </svg>
            </button>
        </div>

        <div class="flex justify-between items-center mt-6 border-t border-gray-200 pb-0 pt-4">
            <button onclick="goToStep(1)" class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md text-sm transition-colors"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-4 h-4">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>Previous Step </button>
            <!-- <button onclick="goToStep(1)" class="text-sm text-gray-600">← Previous</button> -->
            <p class="text-sm text-gray-500 text-center">Step 2 of 4</p>
        </div>

    </div>

    <!-- Step 3 -->
    <div id="step3" class="hidden max-w-7xl mx-auto bg-white p-6 rounded-lg shadow space-y-6 mt-6">

        <!-- Step Title -->
        <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm p-4 rounded-md mb-6">
            <strong class="block font-medium">Step 3: Assign Customer Template</strong>
            <p class="mt-1">Select an existing customer template or create a new one. This template will be assigned to your checklist system for delivery and return processes.</p>
        </div>

        <div class="flex items-center justify-between mb-5">
            <h3 class="text-md font-semibold text-gray-800">Select Customer Template</h3>
        </div>
        <!--  Filters (like step 2) -->
        <div class="grid md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block">Search Templates</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input id="customerTemplateSearch"
                        type="text"
                        placeholder="Search customer templates..."
                        class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-md">
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block">Equipment Category</label>
                {!! html()->select(
                'customer_equipment_category',
                ['' => 'All Categories'] + $equipmentCategories,
                old('customer_equipment_category')
                )
                ->class('w-full border px-3 py-2 rounded-md text-sm') !!}
            </div>
        </div>


        <!-- Customer Admin Templates -->
        <form id="templateFormcustomer" class="space-y-4 grid md:grid-cols-2 gap-4">
            @foreach ($customeradmintemplate as $template)
            <input type="radio"
                name="templatetwo"
                id="customer_template_{{ $template->id }}"
                class="card-radio-step-two hidden"
                value="{{ $template->id }}"
                data-id="{{ $template->id }}"
                @checked($checklistmaster->customer_admin_template_id == $template->id)
            data-template-name="{{ $template->template_name }}" />

            <label for="customer_template_{{ $template->id }}"
                class="customer-template-card flex justify-between gap-4 mb-0 border border-gray-200 rounded-lg p-4 cursor-pointer transition-all">

                <!-- Left section -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-top gap-3">
                        <x-heroicon-o-user class="w-6 h-6 text-indigo-600" />
                        <div>
                            <p class="template-name font-semibold text-gray-900 leading-tight">
                                {{ $template->template_name }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ $template->equipmentCategory->getHierarchyLabel() ?? 'No Category' }}
                            </p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ $template->description ?? 'No description' }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $template->questions->count() }}
                        {{ Str::plural('question', $template->questions->count()) }}
                    </p>
                </div>

                <!-- Right section -->
                <div class="flex flex-col justify-between items-end">
                    <svg class="checkmark w-6 h-6 text-indigo-600"
                        fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-xs px-3 py-1 rounded-full mt-auto
                             {{ $template->active_template ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $template->active_template ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </label>
            @endforeach
        </form>


        <div id="templatecustomer" class="hidden mt-6 bg-indigo-50 border border-indigo-200 text-indigo-800 p-4 rounded-md text-sm">
            <div class="flex items-center gap-2 font-semibold mb-2 flex-wrap">
                <!-- Check icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>

                <!-- Inline label and dynamic value -->
                <span>Template Selected:</span>
                <span id="selectedTemplateCustomer">-</span>
            </div>

            <p class="text-sm text-indigo-900 leading-snug">
                This template will be assigned to your checklist system for customer delivery/return checklists.
            </p>
        </div>


        <!-- Action Buttons -->
        <div class="flex flex-col md:flex-row justify-between mt-8 gap-4">
            <a href="{{ route('admin.checklist-management.customer-admin.index') }}" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">Cancel New Checklist and Create Customer Admin Template</a>
            <button id="continueStep3Btn" onclick="goToStep(4)"
                class="bg-gray-200 text-gray-500 cursor-not-allowed px-4 py-2 rounded-md text-sm font-medium md:w-auto flex items-center gap-2"> Continue to Customer Template
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-4 h-4">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                </svg>
            </button>
        </div>

        <div class="flex justify-between items-center mt-6 border-t border-gray-200 pb-0 pt-4">
            <button onclick="goToStep(2)" class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md text-sm transition-colors"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-4 h-4">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>Previous Step
            </button>
            <!-- <button onclick="goToStep(1)" class="text-sm text-gray-600">← Previous</button> -->
            <p class="text-sm text-gray-500 text-center">Step 3 of 4</p>
        </div>

    </div>

    <div id="step4" class="hidden max-w-4xl mx-auto text-center py-12">
        {{ html()->form()->attributes([
    'method' => 'POST',   {{-- must stay POST, spoofing will add PUT --}}
        'id' => 'checklistmasterForm',
        'autocomplete' => 'off',
        'data-parsley-validate' => true,
        'action' => route('admin.checklist-management.checklist-master.update', $checklistmaster->unique_id),
        ])->open() }}
        @csrf
        @method('PUT')


        {{-- Hidden fields to collect step values --}}
        <input type="hidden" name="checklist_system_name" id="checklistSystemName" value="{{ $checklistmaster->checklist_system_name }}">
        <input type="hidden" name="equipment_category_id" id="hiddenCategoryId" value="{{ $checklistmaster->equipment_category_id }}">
        <input type="hidden" name="rental_ready_template_id" id="hiddenRentalTemplateId" value="{{ $checklistmaster->rental_ready_template_id }}">
        <input type="hidden" name="customer_admin_template_id" id="hiddenCustomerTemplateId" value="{{ $checklistmaster->customer_admin_template_id }}">




        <div class="flex flex-col items-center text-center max-w-4xl w-full">
            <!-- Success Icon -->
            <div class="mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <!-- Heading and Description -->
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Checklist Master System Complete!</h1>
            <p class="text-gray-600 text-sm sm:text-base mb-6">
                Your checklist master system "<strong class="font-semibold text-gray-800 "> <span class="Named-checklist-system"> aa </span> </strong>" has been successfully edited with both rental ready and customer checklist templates.
            </p>

            <!-- What You've Created -->
            <div class="bg-green-50 border border-green-200 text-green-900 rounded-lg p-4 sm:p-6 w-full shadow-sm mb-6">
                <h3 class="font-medium text-green-900  sm:text-md mb-4 font-semibold text-lg">✅ What You've Edited:</h3>
                <ul class="space-y-2 text-sm sm:text-sm text-left">
                    <li class="flex items-start sm:items-center gap-2 text-green-800 flex-wrap">
                        <svg class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="flex-1">Named checklist master system: <span class="font-medium Named-checklist-system">"aa"</span></span>
                    </li>
                    <li class="flex items-start sm:items-center gap-2 text-green-800 flex-wrap">
                        <svg class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="flex-1">Assigned rental ready template: <span class="font-medium" id="Assigned-rental-ready-template">"template-power-equipment"</span></span>
                    </li>
                    <li class="flex items-start sm:items-center gap-2 text-green-800 flex-wrap">
                        <svg class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="flex-1">Assigned customer template: <span class="font-medium" id="Assigned-customer-template"> "ctemplate-power" </span></span>
                    </li>
                    <li class="flex items-start sm:items-center gap-2 text-green-800 flex-wrap">
                        <svg class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="flex-1">Complete checklist system ready for assignment</span>
                    </li>
                </ul>
            </div>


            <!-- Next Steps -->
            <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-lg p-4 w-full shadow-sm mb-6">
                <div class="flex items-center gap-2 mb-2 justify-center">
                    <p class="font-semibold text-blue-800">📋 Next Steps</p>
                </div>
                <p class="text-sm text-center">
                    Your checklist system is now ready to be assigned to equipment items through the Equipment Management screen.
                </p>
            </div>

            <!-- Button -->
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-md text-sm flex items-center gap-2 shadow">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save w-4 h-4">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Save & Return to Equipment Mgt.
            </button>

        </div>

        {{ html()->form()->close() }}
    </div>


</section>



@endsection

@push('js')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rentalTemplateId = document.getElementById('hiddenRentalTemplateId').value;
        if (rentalTemplateId) {
            const selectedRadio = document.querySelector(`input[name="template"][data-id="${rentalTemplateId}"]`);
            if (selectedRadio) {
                selectedRadio.checked = true;
                document.getElementById('templateSummary').classList.remove('hidden');
                document.getElementById('selectedTemplate').textContent = selectedRadio.value;
                document.getElementById('Assigned-rental-ready-template').textContent = `"${selectedRadio.value}"`;
                document.getElementById('continueStep2Btn').disabled = false;
            }
        }

        // ---- Customer Admin Templates ----
        const customerTemplateId = document.getElementById('hiddenCustomerTemplateId').value;
        if (customerTemplateId) {
            const selectedRadio2 = document.querySelector(`input[name="templatetwo"][data-id="${customerTemplateId}"]`);
            if (selectedRadio2) {
                selectedRadio2.checked = true;
                document.getElementById('templatecustomer').classList.remove('hidden');
                document.getElementById('selectedTemplateCustomer').textContent = selectedRadio2.dataset.templateName;
                document.getElementById('Assigned-customer-template').textContent = `"${selectedRadio2.dataset.templateName}"`;
                document.getElementById('continueStep3Btn').disabled = true;
            }
        }

    });

    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('systemName');
        if (input.value.trim() !== '') {
            toggleContinue(); // enable the button right away
        }
    });
</script>


<script>
    function toggleContinue() {
        const input = document.getElementById('systemName');
        const btn = document.getElementById('continueBtn');

        const newValue = '"' + input.value.trim() + '"';

        //  Update all elements with class "Named-checklist-system"
        document.querySelectorAll('.Named-checklist-system').forEach(el => {
            el.textContent = newValue;
        });

        // store hidden input value
        document.getElementById('checklistSystemName').value = input.value.trim();


        //  Update header title
        updateStepHeaderTitle(input.value.trim());

        if (input.value.trim() !== '') {
            btn.classList.remove('bg-gray-200', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.add('bg-green-600', 'text-white', 'hover:bg-green-700', 'cursor-pointer');
            btn.disabled = false;
        } else {
            btn.classList.add('bg-gray-200', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700', 'cursor-pointer');
            btn.disabled = true;
        }

         toggleContinueWithCategory();
    }

    
    function storeCategoryId(select) {
        const hiddenCategoryId = document.getElementById('hiddenCategoryId');
        hiddenCategoryId.value = select.value;

        // console.log('Selected Category ID:', hiddenCategoryId.value);

        toggleContinueWithCategory();
    }

    function toggleContinueWithCategory() {
        const systemName = document.getElementById('systemName').value.trim();
        const categoryId = document.getElementById('hiddenCategoryId').value;
        const btn = document.getElementById('continueBtn');

        if (systemName !== '' && categoryId !== '') {
            btn.classList.remove('bg-gray-200', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700', 'cursor-pointer');
            btn.disabled = false;
        } else {
            btn.classList.add('bg-gray-200', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700', 'cursor-pointer');
            btn.disabled = true;
        }
    }

    function updateStepHeaderTitle(name) {
        const titlePlaceholder = document.querySelector('.checklist-title-display');
        if (titlePlaceholder) {
            titlePlaceholder.textContent = name;
        }
    }

    // Keep track of which steps are unlocked
    let unlockedSteps = [1, 2, 3, 4]; // for Edit
    // Step 1 is always unlocked


    function goToStep(step) {
        for (let i = 1; i <= 4; i++) {
            const content = document.getElementById('step' + i);
            if (content) content.classList.add('hidden');
            const icon = document.getElementById('stepIcon' + i);
            if (icon) {
                icon.className = 'w-8 h-8 rounded-full border border-gray-300 text-gray-600 flex items-center justify-center font-bold';
                icon.textContent = i;
            }
        }
        const active = document.getElementById('step' + step);
        if (active) active.classList.remove('hidden');
        const header = document.getElementById('stepHeader');
        if (step > 1) header.classList.remove('hidden-step-header');
        else header.classList.add('hidden-step-header');
        for (let i = 1; i < step; i++) {
            const icon = document.getElementById('stepIcon' + i);
            if (icon) {
                icon.className = 'w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center font-bold';
                icon.textContent = '✓';
            }
        }
        const currentIcon = document.getElementById('stepIcon' + step);
        if (currentIcon) {
            currentIcon.className = 'w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold';
            currentIcon.textContent = step;
        }

        //  Update subtitle dynamically
        const subtitle = document.getElementById('stepSubtitle');
        if (subtitle) {
            switch (step) {
                case 1:
                    subtitle.textContent = "Update the name of your checklist system";
                    break;
                case 2:

                    subtitle.textContent = "Step 2 of 4: Assign Rental Ready Template";
                    break;
                case 3:
                    subtitle.textContent = "Step 3 of 4: Assign Customer Template";
                    break;
                case 4:
                    subtitle.textContent = "Step 4 of 4: Complete & Save";
                    break;
            }
        }

        if (!unlockedSteps.includes(step)) {
            unlockedSteps.push(step);
        }


    }
</script>

<script>
    function validateStep2BeforeContinue() {

        const selectedTemplate = document.querySelector('input[name="template"]:checked');
        // const hiddenCategoryId = document.getElementById('hiddenCategoryId');
        const hiddenRentalTemplateId = document.getElementById('hiddenRentalTemplateId');

        if (selectedTemplate) {
            // Assign category ID from selected card
            // hiddenCategoryId.value = selectedTemplate.getAttribute('data-category-id');

            // console.log('Category ID:', hiddenCategoryId.value);

            // Assign template ID too (if needed)
            hiddenRentalTemplateId.value = selectedTemplate.getAttribute('data-id');

            //  Proceed to next step
            goToStep(3);
        }
    }
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const stepHeaders = document.querySelectorAll('[id^="step-"][id$="-redirect"]');

        stepHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const stepNumber = parseInt(this.id.split('-')[1]); // e.g. "step-2-redirect" → 2
                if (unlockedSteps.includes(stepNumber)) {
                    goToStep(stepNumber);
                } else {
                    console.log(`Step ${stepNumber} is locked`);
                }
            });
        });
    });
</script>

<script>
    const radios = document.querySelectorAll('input[name="template"]');
    const summary = document.getElementById('templateSummary');
    const selectedTemplate = document.getElementById('selectedTemplate');
    const continueBtn = document.getElementById('continueStep2Btn');

    // Disable button initially
    continueBtn.disabled = true;

    radios.forEach(radio => {
        radio.addEventListener('change', () => {
            summary.classList.remove('hidden');
            selectedTemplate.textContent = radio.value;


            //  also update final summary box
            const assignedrentalreadytemplate = document.getElementById('Assigned-rental-ready-template');
            document.getElementById('hiddenRentalTemplateId').value = radio.dataset.id;


            // document.getElementById('hiddenCategoryId').value = radio.dataset.categoryId;

            if (assignedrentalreadytemplate) {
                assignedrentalreadytemplate.textContent = '"' + radio.value + '"';
            }

            // Enable button when a template is selected
            continueBtn.disabled = false;
            continueBtn.classList.remove('bg-gray-200', 'text-gray-500', 'cursor-not-allowed');
            continueBtn.classList.add('bg-green-600', 'text-white', 'hover:bg-green-700', 'cursor-pointer');
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const radios = document.querySelectorAll('input[name="templatetwo"]');
        const customerBox = document.getElementById('templatecustomer');
        const selectedCustomer = document.getElementById('selectedTemplateCustomer');
        const continue3Btn = document.getElementById('continueStep3Btn');

        // --- Always keep Step 3 Continue button enabled for Edit mode ---
        continue3Btn.disabled = false;
        continue3Btn.classList.remove('bg-gray-200', 'text-gray-500', 'cursor-not-allowed');
        continue3Btn.classList.add('bg-green-600', 'text-white', 'hover:bg-green-700', 'cursor-pointer');

        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                customerBox.classList.remove('hidden');
                selectedCustomer.textContent = this.dataset.templateName;

                // Update final summary
                const assignedcustomertemplate = document.getElementById('Assigned-customer-template');
                if (assignedcustomertemplate) {
                    assignedcustomertemplate.textContent = '"' + this.dataset.templateName + '"';
                }

                document.getElementById('hiddenCustomerTemplateId').value = this.value;
            });
        });

        // Continue to Step 4 when clicked
        continue3Btn.addEventListener('click', function() {
            goToStep(4);
        });
    });
</script>



<!--  JS Filter -->

<!-- JS Filter -->
<script>
    const searchInput = document.getElementById('templateSearch');
    const categorySelect = document.querySelector('select[name="equipment_category"]');
    const cards = document.querySelectorAll('#templateForm .template-card');

    function filterTemplates() {
        let search = searchInput.value.toLowerCase();
        let selectedCategory = categorySelect.value;

        cards.forEach(function(card) {
            let name = card.querySelector('.template-name').innerText.toLowerCase();
            let description = card.querySelector('p.text-sm').innerText.toLowerCase();
            let category = card.querySelector('.text-sm.text-gray-600').innerText.trim();

            let matchesSearch = name.includes(search) || description.includes(search);
            let matchesCategory = selectedCategory === "" || category === categorySelect.options[categorySelect.selectedIndex].text;

            if (matchesSearch && matchesCategory) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterTemplates);
    categorySelect.addEventListener('change', filterTemplates);
</script>


<script>
    // Step 3 Filter Logic
    const customerSearchInput = document.getElementById('customerTemplateSearch');
    const customerCategorySelect = document.querySelector('select[name="customer_equipment_category"]');
    const customerCards = document.querySelectorAll('#templateFormcustomer .customer-template-card');

    function filterCustomerTemplates() {
        let search = customerSearchInput.value.toLowerCase();
        let selectedCategory = customerCategorySelect.value;
        let selectedCategoryText = customerCategorySelect.options[customerCategorySelect.selectedIndex]?.text?.trim() || "";

        customerCards.forEach(function(card) {
            let name = card.querySelector('.template-name').innerText.toLowerCase();
            let description = card.querySelector('p.text-sm').innerText.toLowerCase();
            let category = card.querySelector('.text-sm.text-gray-600').innerText.trim();

            let matchesSearch = name.includes(search) || description.includes(search);
            let matchesCategory = selectedCategory === "" || category === selectedCategoryText;

            card.style.display = (matchesSearch && matchesCategory) ? 'flex' : 'none';
        });
    }

    customerSearchInput.addEventListener('input', filterCustomerTemplates);
    customerCategorySelect.addEventListener('change', filterCustomerTemplates);
</script>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        const saveButton = document.getElementById('savethetemp');
        const form = document.getElementById('checklistmasterForm');

        if (saveButton && form) {
            saveButton.addEventListener('click', function(e) {
                e.preventDefault();
                saveButton.disabled = true;
                saveButton.innerHTML = 'Saving...';
                form.submit();
            });
        }
    });
</script>


@endpush

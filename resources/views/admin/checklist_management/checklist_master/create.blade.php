@extends('admin.layouts.app')

@section('title', 'Create Checklist Master')

@push('css')

@endpush

@section('content')
    
    @include('flash::message')

    <div class="bg-white  flex flex-col md:flex-row items-start md:items-center justify-between w-full border-b border-gray-200 px-2 py-2">
        <!-- Left Side: Icon, Title, Description -->
        <div class="flex items-start gap-3">
            <x-heroicon-o-document class="w-8 h-8 text-blue-600" />
            <div>
                <!-- <h2 class="text-xl font-semibold text-gray-900">Create New Checklist System</h2> -->
                <h2 class="text-xl font-semibold text-gray-900">Create Checklist System: <span class="checklist-title-display">-</span></h2>
                <!-- <p class="text-sm text-gray-600">Step 2 of 4: <span class="checklist-title-display">-</span>Assign Rental Ready Template</p> -->
                <p class="text-sm text-gray-600">Set up a complete checklist system for equipment</p>
            </div>
        </div>

        <!-- Right Side: Cancel Button -->
        <div class="mt-4 md:mt-0">
            <a href="https://admin.kabba.local/checklist_management/checklist-master" class="flex items-center text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Cancel
            </a>
        </div>
    </div>

    <!-- Step Header (Hidden initially and shown on step 2+) -->
    <div id="stepHeader" class="mb-6 hidden-step-header  mx-auto px-2 sm:px-2 lg:px-2 py-2 border-b border-gray-200">
        <div class="flex flex-wrap justify-between items-center text-sm text-gray-700 gap-y-4">
            <div class="flex items-center gap-3">
                <div id="stepIcon1" class="w-8 h-8 rounded-full border border-gray-300 text-gray-600 flex items-center justify-center font-bold">1</div>
                <div>
                    <p class="font-semibold">Name Your Checklist</p>
                    <p class="text-xs text-gray-500">Create and name your checklist system</p>
                </div>
            </div>
            <div class="hidden md:block text-gray-300">➝</div>
            <div class="flex items-center gap-3">
                <div id="stepIcon2" class="w-8 h-8 rounded-full border border-gray-300 text-gray-600 flex items-center justify-center font-bold">2</div>
                <div>
                    <p class="font-semibold">Assign Rental Ready Template</p>
                    <p class="text-xs text-gray-500">Select rental ready checklist template</p>
                </div>
            </div>
            <div class="hidden md:block text-gray-300">➝</div>
            <div class="flex items-center gap-3">
                <div id="stepIcon3" class="w-8 h-8 rounded-full border border-gray-300 text-gray-600 flex items-center justify-center font-bold">3</div>
                <div>
                    <p class="font-semibold">Assign Customer Template</p>
                    <p class="text-xs text-gray-500">Select customer checklist template</p>
                </div>
            </div>
            <div class="hidden md:block text-gray-300">➝</div>
            <div class="flex items-center gap-3">
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
        <div id="step1" class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow space-y-6 mt-6">
            <div>
                <!-- Header Icon and Title -->
                <div class="flex flex-col items-center text-center space-y-1 mb-8">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list w-16 h-16 text-blue-600 mx-auto mb-4"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path></svg>
                    <h2 class="text-2xl font-bold text-gray-900">Name Your Checklist System</h2>
                    <p class="text-gray-600 text-sm">Create a complete checklist system by selecting from existing templates.</p>
                </div>

                <!-- Warning Box -->
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-md space-y-3 mb-6">
                    <div class="flex items-start gap-2">
                        <div>
                            <p class="font-semibold mb-2">⚠️ Before You Continue</p>
                            <p class="text-md">
                                You will need to select from existing Rental Ready and Customer Checklists. If you need to create a new Rental Ready or Customer Checklist, please do this now before creating a new Checklist System.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center gap-2"><x-heroicon-o-cog-6-tooth  class="w-4 h-4" /> Rental Ready Admin</button>
                        <button class="bg-purple-600 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center gap-2"><x-heroicon-o-users class="w-4 h-4" />Customer Admin</button>
                    </div>
                </div>

                <!-- Input -->
                <div class="space-y-1">
                    <label for="systemName" class="font-medium text-gray-700 mb-2">Checklist System Name <span class="text-red-500">*</span></label>
                    <input id="systemName" oninput="toggleContinue()" type="text" placeholder="e.g., Standard Heavy Equipment System" class="w-full border px-3 py-2 rounded-md text-sm" />
                    <p class="text-sm text-gray-500 mt-2 ">Choose a descriptive name that identifies this checklist system. You can assign it to multiple equipment items later.</p>
                </div>

                <!-- What Happens Next -->
                <div class="bg-blue-50 border border-blue-100 rounded-md p-4 mt-6">
                    <h4 class="text-sm font-semibold text-blue-900 mb-2">What happens next?</h4>
                    <ul class="text-sm text-blue-900 list-decimal list-inside space-y-1">
                        <li>Create or select rental ready checklist questions</li>
                        <li>Build your rental ready template</li>
                        <li>Create or select customer checklist questions</li>
                        <li>Build your customer checklist template</li>
                        <li>Your complete system will be ready to assign to equipment</li>
                    </ul>
                </div>

                <!-- System Assignment -->
                <div class="bg-gray-50 border border-gray-200 rounded-md p-4 flex items-start gap-3 mt-6">
                    <x-heroicon-o-document-text class="w-6 h-6 text-blue-900" />
                    <div>
                        <p class="font-semibold text-gray-700">System Assignment</p>
                        <p class="text-sm text-gray-600">Once created, this checklist system can be assigned to multiple equipment items (3, 5, 12, or more) through the Equipment Profile screen in a separate module.</p>
                    </div>
                </div>

                <div class="flex items-center gap-6 pt-6">
                    <button id="continueBtn" onclick="goToStep(2)"  disabled class="flex items-center gap-2 px-6 py-2 bg-gray-200  rounded-md text-gray-500 cursor-not-allowed flex-1 justify-center text-sm font-medium ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save w-4 h-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-7 4h8a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Continue to Questions
                    </button>
                    <a href="#" class="text-sm text-gray-600 hover:underline">Cancel</a>
            
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div id="step2" class="hidden">
            
            <!-- Step Title -->
            <div class="bg-green-50 border border-green-200 text-green-800 text-sm p-4 rounded-md mb-6">
                <strong class="block font-medium">Step 2: Assign Rental Ready Template</strong>
                <p class="mt-1">Select an existing rental ready template or create a new one. This template will be assigned to your checklist system for equipment inspections.</p>
            </div>

            <!-- Filters -->
            <div class="grid md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Search Templates</label>
                    <input type="text" class="w-full border px-3 py-2 rounded-md text-sm" placeholder="Search rental ready templates..." />
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Equipment Category</label>
                    <select class="w-full border px-3 py-2 rounded-md text-sm">
                        <option>All Categories</option>
                        <option>Compact Equipment</option>
                        <option>Power Equipment</option>
                        <option>Heavy Equipment</option>
                    </select>
                </div>
            </div>

            <!-- Card Options as Radio Buttons -->
            <form id="templateForm" class="space-y-4 grid md:grid-cols-2 gap-4">
   
                <input type="radio" name="template" id="compact" class="card-radio-step hidden" value="Compact Equipment Standard" />
                    <label for="compact" class="flex justify-between gap-4 border border-gray-200 rounded-lg p-4 cursor-pointer transition-all">
                  <!-- Left section: icon and details -->
    <div class="flex flex-col gap-2">
      <div class="flex items-center gap-3">
        <!-- Icon -->
        <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-7 4h8a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <div>
          <p class="font-semibold text-gray-900 leading-tight">Compact Equipment Standard</p>
          <p class="text-sm text-gray-600">Compact Equipment</p>
        </div>
      </div>
      <p class="text-sm text-gray-600 mt-1">Standard checklist for compact equipment like skid steers and mini excavators</p>
      <p class="text-sm text-gray-600 mt-1">10 questions</p>
    </div>

    <!-- Right section: checkmark + status -->
    <div class="flex flex-col justify-between items-end">
      <svg class="checkmark w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
      </svg>
      <span class="text-xs bg-green-100 text-green-800 font-medium px-3 py-1 rounded-full mt-auto">Active</span>
    </div>
                </label>

                <input type="radio" name="template" id="heavy" class="card-radio-step hidden" value="Heavy Equipment Standard" />
                <label for="heavy" class="flex justify-between items-start border rounded-md p-4 cursor-pointer transition-all">
                    <div>
                        <h4 class="font-semibold text-gray-900">Heavy Equipment Standard</h4>
                        <p class="text-sm text-gray-500">Standard checklist for heavy equipment like excavators, bulldozers, and loaders</p>
                        <div class="flex justify-between items-center mt-4">
                            <span class="text-sm text-gray-600">12 questions</span>
                            <span class="text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full font-medium">Active</span>
                        </div>
                    </div>
                    <svg class="checkmark w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </label>

                <input type="radio" name="template" id="power" class="card-radio-step hidden" value="Power Equipment Standard" />
                <label for="power" class="flex justify-between items-start border rounded-md p-4 cursor-pointer transition-all">
                    <div>
                        <h4 class="font-semibold text-gray-900">Power Equipment Standard</h4>
                        <p class="text-sm text-gray-500">Standard checklist for power equipment like generators and compressors</p>
                        <div class="flex justify-between items-center mt-4">
                            <span class="text-sm text-gray-600">10 questions</span>
                            <span class="text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full font-medium">Active</span>
                        </div>
                    </div>
                    <svg class="checkmark w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </label>
            </form>

            <!-- Template Selected Box -->
            <div id="templateSummary" class="hidden mt-6 bg-green-50 border border-green-200 text-green-800 p-4 rounded-md text-sm">
                <strong>Template Selected:</strong> <span id="selectedTemplate">-</span><br />
                This template will be assigned to your checklist system for rental ready inspections.
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col md:flex-row justify-between mt-8 gap-4">
                <button class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">Cancel New Checklist and Create Rental Ready Template</button>
                <button onclick="goToStep(3)"  class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium md:w-auto">Continue to Customer Template →</button>
            </div>

            <div class="flex justify-between items-center mt-6">
                <button onclick="goToStep(1)" class="text-sm text-gray-600">← Previous</button>
                 <p class="text-xs text-gray-500 text-center mt-4">Step 2 of 4</p>
            </div>
           
        </div>
    </section>



    @endsection

@push('js')

<script>
function toggleContinue() {
  const input = document.getElementById('systemName');
  const btn = document.getElementById('continueBtn');

   updateStepHeaderTitle(input.value.trim()); // 👈 Add this line 

  if (input.value.trim() !== '') {
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
}
</script>

<script>
  const radios = document.querySelectorAll('input[name="template"]');
  const summary = document.getElementById('templateSummary');
  const selectedTemplate = document.getElementById('selectedTemplate');

  radios.forEach(radio => {
    radio.addEventListener('change', () => {
      summary.classList.remove('hidden');
      selectedTemplate.textContent = radio.value;
    });
  });
</script>

@endpush

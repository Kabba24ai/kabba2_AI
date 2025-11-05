   <!-- Inner Tab Contents -->
   <div id="questionssub" class="tab-content" data-tab-group="inner">
       <div class="space-y-6 p-4" id="questionWrapper">
           <!-- Header -->
           <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
               <div>
                   <h2 class="text-xl font-semibold text-gray-900">Questions</h2>
                   <p class="text-gray-600">Create and manage inspection questions and answer options</p>
               </div>
               <a href="javascript:void(0)" onclick="openQuestionModal()"
                   class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 mt-3">
                   + New Question
               </a>
           </div>
           <!-- Top Filters -->
           <div class="bg-white border border-gray-200 rounded-lg p-4 space-y-4 shadow-sm">
               <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                   <!-- Search -->
                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-1">Search Questions</label>
                       <input type="text" placeholder="Search by question name..."
                           class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                   </div>

                   <!-- Filter -->
                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Category</label>
                       <select
                           class="w-full text-sm px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                           <option>All Categories</option>
                           @foreach ($rentalreadycategory as $category)
                           <option value="{{ $category->id }}"> {{ $category->category_name }} </option>
                           @endforeach

                       </select>
                   </div>

                   <!-- Answer Visibility Toggle -->
                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-1">Answer Visibility</label>
                       <button id="toggleGlobalAnswers"
                           class="w-full font-medium px-4 py-2 text-sm rounded-md flex items-center justify-center gap-2 border border-blue-300 text-blue-600 hover:bg-gray-50 transition">
                           <svg id="icon-show" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                               viewBox="0 0 24 24">
                               <path stroke-linecap="round" stroke-linejoin="round"
                                   d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                               <path stroke-linecap="round" stroke-linejoin="round"
                                   d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                           </svg>
                           <svg id="icon-hide" class="w-5 h-5 text-gray-900 hidden" fill="none" stroke="currentColor"
                               stroke-width="2" viewBox="0 0 24 24">
                               <path stroke-linecap="round" stroke-linejoin="round"
                                   d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.042 10.042 0 013.03-4.362M6.873 6.876A9.953 9.953 0 0112 5c4.477 0 8.267 2.943 9.541 7a9.966 9.966 0 01-1.249 2.527M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                               <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                           </svg>
                           <span id="toggleText">Show All Answers</span>
                       </button>
                   </div>
               </div>
           </div>

           <style>
               .drag-handle {
                   touch-action: none;
                   -webkit-user-select: none;
                   -webkit-touch-callout: none;
               }


               .drag-handle svg {
                   pointer-events: none;
               }
           </style>

           <!-- Wrapper around all cards -->
           <div id="cardsWrapper">
               @foreach($rentalreadycategory as $category)
               @foreach($category->questions as $question)
               <!-- Sample Question Card -->
               <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm question-card mb-4" data-required="{{ $question->required_question }}" data-category-id="{{ $category->id }}">
                   <!-- Header -->
                   <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                       <div>
                           <div class="flex items-center gap-2">
                               <h3 class="text-base font-semibold text-gray-900"> {{ $question->question_name }}</h3>

                               <!-- @if($question->required_question)
                               <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full font-medium">Required</span>
                               @endif -->

                           </div>
                           <p class="text-sm text-gray-600 mt-1">Category: {{ $category->category_name }} </p>
                       </div>
                       <div class="flex items-center gap-4 mt-3 md:mt-0 text-gray-600 text-sm" data-count="{{ $question->answers->count() }}">
                           <button class="toggle-answers text-blue-600 hover:underline flex items-center gap-1">
                               <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor"
                                   stroke-width="2" viewBox="0 0 24 24">
                                   <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                               </svg>
                               <span>Show Questions ({{ $question->answers->count() }}) </span>
                           </button>
                           <!-- Edit -->

                           @php
                           $options = $question->answers

                           ->map(function($answer) {
                           return [
                           "id" => $answer->id,
                           "text" => $answer->answer_name,
                           "status" => $answer->type,
                           "index_number" => $answer->index_number,
                           ];
                           })
                           ->values();

                           $optionsJson = $options->toJson();
                           @endphp

                           <!-- Edit Button for Questions -->
                           <button class="edit-question-btn text-green-600 hover:text-green-800"
                               data-id="{{ $question->id }}"
                               data-name="{{ $question->question_name }}"
                               data-category="{{ $question->category_id }}"
                               data-required="{{ $question->required_question ? 1 : 0 }}"
                               data-options='{{ $optionsJson }}'
                               data-route="{{ route('admin.checklist-management.rental-ready.questions.update', $question->id) }}"
                               title="Edit">
                               <x-heroicon-o-pencil class="w-5 h-5" />
                           </button>



                           <!-- Delete -->
                           <form action="{{ route('admin.checklist-management.rental-ready.questions.delete', $question->unique_id) }}"
                               method="POST"
                               class="inline delete-question-form"
                               data-question-name="{{ $question->question_name }}">
                               @csrf
                               @method('DELETE')
                               <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                   <x-heroicon-o-trash class="w-5 h-5" />
                               </button>
                           </form>

                       </div>
                   </div>

                   <!-- Answers (initially hidden) -->
                   <div class="answers hidden mt-5 border-t pt-5 space-y-2">
                       <h4 class="text-sm font-semibold text-gray-800">Answer Options:</h4>
                       <div class="grid sm:grid-cols-2 gap-3">

                           @foreach($question->answers as $index => $answer)
                           @php
                           $statusMap = [
                           'Rental Ready' => [
                           'bg' => 'bg-green-100 text-green-700',
                           'icon' => ''
                           ],
                           'Maint. Hold' => [
                           'bg' => 'bg-yellow-100 text-yellow-700',
                           'icon' => ''
                           ],
                           'Damaged' => [
                           'bg' => 'bg-red-100 text-red-600',
                           'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                               viewBox="0 0 24 24">
                               <path stroke-linecap="round" stroke-linejoin="round"
                                   d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a1.75 1.75 0 001.51 2.62h17.34a1.75 1.75 0 001.51-2.62L13.71 3.86a1.75 1.75 0 00-3.42 0z" />
                           </svg>'
                           ],
                           ];

                           $status = $statusMap[$answer->type] ?? $statusMap['Rental Ready'];
                           @endphp

                           <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                               <div class="flex items-center gap-3">
                                   <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">
                                       {{ $index + 1 }}
                                   </span>
                                   <span class="flex items-center gap-1 text-black text-sm font-medium">

                                       {{-- Icon based on type --}}
                                       @if($answer->type === 'Rental Ready')
                                       <x-heroicon-o-check class="w-5 h-5 text-green-700" />
                                       @elseif($answer->type === 'Maint. Hold')
                                       <x-heroicon-o-clock class="w-5 h-5 text-yellow-700" />
                                       @elseif($answer->type === 'Damaged')
                                       <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2"
                                           viewBox="0 0 24 24">
                                           <path stroke-linecap="round" stroke-linejoin="round"
                                               d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a1.75 1.75 0 001.51 2.62h17.34a1.75 1.75 0 001.51-2.62L13.71 3.86a1.75 1.75 0 00-3.42 0z" />
                                       </svg>
                                       @endif

                                       {{ $answer->answer_name }}
                                   </span>
                               </div>
                               <span class="{{ $status['bg'] }} text-xs px-2 py-0.5 rounded-full">
                                   {{ $answer->type ?? 'N/A' }}
                               </span>
                           </div>
                           @endforeach



                       </div>
                   </div>
               </div>
               @endforeach
               @endforeach
           </div>
       </div>
   </div>




   <!--Question Modal -->
   <div id="questionModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
       <div class="modal-scrollable w-full mx-auto">
           <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-3xl flex flex-col max-h-full overflow-hidden border border-gray-200">
               <div class="flex justify-between items-center px-6 pt-4">
                   <h3 class="text-lg font-semibold text-gray-800">New Question</h3>
                   <button onclick="closeQuestionModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
               </div>

               <!-- Scrollable Content -->
               <div class=" overflow-y-auto max-h-[70vh]">
                   <!-- {{-- Form --}} -->
                   {{ html()->form('POST', route('admin.checklist-management.rental-ready.questions.store'))
                ->id('questionForm')
                ->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'flex flex-col flex-1'
                ])
                ->acceptsFiles()
                ->open() }}


                   <input type="hidden" name="options" id="optionsInput">


                   <div class="max-w-3xl px-6 py-4 space-y-6 overflow-y-auto">

                       <div>
                           <label class="block text-sm font-medium text-gray-700 mb-1">Question Name *</label>
                           <!-- <input type="text" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"> -->

                           {!! html()->text('question_name')
                           ->class('w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500')
                           ->attributes(['id' => 'question_name', 'placeholder' => 'Enter Question name'])
                           ->required() !!}

                       </div>

                       <div>
                           <label class="block text-sm font-medium text-gray-700 mb-1">Question Category *</label>
                           {!! html()->select(
                           'category_id',
                           ['' => '-- Select Category --'] + $rentalreadycategory->pluck('category_name', 'id')->toArray()
                           )
                           ->class('w-full text-sm border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500')
                           ->attributes(['id' => 'category_id'])
                           ->required()
                           !!}


                       </div>

                       <div class="hidden">
                           <label class=" inline-flex items-center gap-2 text-sm text-gray-700">
                               <!-- <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600"> Required Question -->
                               {!! html()->checkbox('required_question', true, 1)
                               ->class('form-checkbox h-4 w-4 text-blue-600') !!}


                           </label>
                       </div>

                       <div class="space-y-4">
                           <div class="flex justify-between items-center">
                               <span class="text-sm font-medium text-gray-700">Answer Options <span class="text-xs text-gray-400 font-normal">(Drag to reorder)</span></span>
                               <button type="button" onclick="addOption()" class="text-blue-600 text-sm font-medium hover:underline">+ Add Option</button>
                           </div>

                           <div id="sortable-list" class="space-y-3"></div>
                       </div>

                   </div>

                   <div class="flex justify-end gap-2 px-6 pb-4">
                       <button type="button" onclick="closeQuestionModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white">Cancel</button>
                       <button type="submit" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600">Save Question</button>
                   </div>

                   {{ html()->form()->close() }}

               </div>
           </div>
       </div>
   </div>


   @push('js')

   <!-- Question -->
   <script>
       document.addEventListener('DOMContentLoaded', function() {
           // --- INDIVIDUAL TOGGLE ---
           document.querySelectorAll('.toggle-answers').forEach(button => {
               button.addEventListener('click', () => {
                   const card = button.closest('.question-card');
                   const answers = card.querySelector('.answers');
                   const icon = button.querySelector('svg');
                   const text = button.querySelector('span');

                   // Use the correct variable to get data-count
                   const count = button.closest('[data-count]')?.dataset.count || 0;

                   const isVisible = !answers.classList.contains('hidden');
                   if (isVisible) {
                       answers.classList.add('hidden');
                       text.textContent = `Show Questions (${count})`;
                       icon.classList.remove('rotate-90');
                   } else {
                       answers.classList.remove('hidden');
                       text.textContent = 'Hide Questions';
                       icon.classList.add('rotate-90');
                   }
               });
           });

           // --- GLOBAL TOGGLE ---
           const globalToggle = document.getElementById('toggleGlobalAnswers');
           const iconShow = document.getElementById('icon-show');
           const iconHide = document.getElementById('icon-hide');
           const toggleText = document.getElementById('toggleText');
           let globalState = false;

           if (globalToggle) {
               globalToggle.addEventListener('click', () => {
                   globalState = !globalState;

                   // Toggle global button UI
                   iconShow.classList.toggle('hidden', globalState);
                   iconHide.classList.toggle('hidden', !globalState);
                   toggleText.textContent = globalState ? 'Hide All Answers' : 'Show All Answers';
                   globalToggle.classList.toggle('border-blue-300', !globalState);
                   globalToggle.classList.toggle('border-gray-300', globalState);
                   globalToggle.classList.toggle('text-blue-600', !globalState);
                   globalToggle.classList.toggle('text-gray-600', globalState);

                   // Toggle all answers
                   document.querySelectorAll('.question-card').forEach(card => {
                       const answers = card.querySelector('.answers');
                       const btn = card.querySelector('.toggle-answers');
                       const span = btn.querySelector('span');
                       const icon = btn.querySelector('svg');

                       const count = btn.closest('[data-count]')?.dataset.count || 0;

                       if (globalState) {
                           answers.classList.remove('hidden');
                           span.textContent = 'Hide Questions';
                           icon.classList.add('rotate-90');
                       } else {
                           answers.classList.add('hidden');
                           span.textContent = `Show Questions (${count})`;
                           icon.classList.remove('rotate-90');
                       }
                   });
               });
           }
       });
   </script>
   <!-- Question -->

   <!-- dragg js  -->
   <script>
       let answerOptions = [{
           id: 1,
           text: 'inspection required',
           status: 'Maint. Hold'
       }];
       let nextId = 2;

       function openQuestionModal() {
           document.getElementById('questionModal').classList.remove('hidden');
           renderOptions();
       }

       function closeQuestionModal() {
           document.getElementById('questionModal').classList.add('hidden');
       }

       let sortableInstance;


       function renderOptions() {
           const container = document.getElementById('sortable-list');
           container.innerHTML = '';

           answerOptions.forEach((option, index) => {
               const wrapper = document.createElement('div');
               wrapper.className =
                   'bg-white border border-gray-300 rounded-md px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:gap-4';
               wrapper.setAttribute('data-id', option.id);

               // disable drag handle & delete for the first option
               const dragHandle = index === 0 ? '' : `
                <div class="flex items-center mb-2 sm:mb-0">
                    <span class="drag-handle w-7 h-7 flex items-center justify-center rounded-full text-gray-900 cursor-move">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10 6h.01M10 10h.01M10 14h.01M14 6h.01M14 10h.01M14 14h.01" />
                        </svg>
                    </span>
                </div>`;

               const deleteButton = index === 0 ? '' : `
                <div>
                    <button type="button" onclick="removeOption(${index})"
                        class="mt-2 sm:mt-0 sm:ml-2 text-red-600 rounded-full w-8 h-8 flex items-center justify-center hover:text-red-800 mx-auto sm:mx-0"
                        title="Delete">
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </button>
                </div>`;

               // Lock first input & select
               const inputDisabled = index === 0 ? 'readonly' : '';
               const selectDisabled = index === 0 ? 'disabled' : '';

               wrapper.innerHTML = `

     ${dragHandle}

    <div class="flex-1">
        <input type="text" placeholder="Answer description..."
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            value="${option.text}" 
            oninput="updateText(${index}, this.value)"  ${inputDisabled} required>
    </div>

    <div>
        <select class="w-full mt-2 sm:mt-0 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            onchange="updateStatus(${index}, this.value)"  ${selectDisabled} >
            <option ${option.status === 'Rental Ready' ? 'selected' : ''}>Rental Ready</option>
            <option ${option.status === 'Maint. Hold' ? 'selected' : ''}>Maint. Hold</option>
            <option ${option.status === 'Damaged' ? 'selected' : ''}>Damaged</option>
        </select>
    </div>

  ${deleteButton}

`;


               container.appendChild(wrapper);
           });



           // Initialize only once
           if (!sortableInstance) {
               // Initialize Sortable
               sortableInstance = Sortable.create(container, {
                   handle: '.drag-handle',
                   animation: 150,
                   delay: 150,
                   delayOnTouchOnly: true,
                   touchStartThreshold: 5,
                   fallbackOnBody: true,
                   onMove: function(evt) {
                       // Prevent dragging the first option OR placing any item before it
                       if (evt.dragged && evt.dragged.getAttribute('data-id') == answerOptions[0].id) {
                           return false; // Can't drag first
                       }
                       if (evt.related && evt.related === container.children[0]) {
                           return false; // Can't drop anything before first
                       }
                   },
                   onEnd: function(evt) {
                       // Prevent reordering if first is involved
                       if (evt.oldIndex === 0 || evt.newIndex === 0) {
                           renderOptions();
                           return;
                       }

                       // Normal reorder among the rest
                       const movedItem = answerOptions.splice(evt.oldIndex, 1)[0];
                       answerOptions.splice(evt.newIndex, 0, movedItem);
                       renderOptions();
                   }
               });
           }


       }

       function addOption() {
           answerOptions.push({
               id: nextId++,
               text: '',
               status: 'Rental Ready'
           });
           renderOptions();
       }

       function removeOption(index) {
           //    if (answerOptions.length > 2) {
           answerOptions.splice(index, 1);
           renderOptions();
           //    } else {
           //        notyf.error("You must have at least 2 options.");
           //    }
       }


       function updateText(index, value) {
           answerOptions[index].text = value;
       }

       function updateStatus(index, value) {
           answerOptions[index].status = value;
       }

       //  Before submitting form, sync options into hidden input
       document.getElementById('questionForm').addEventListener('submit', function(e) {
           document.getElementById('optionsInput').value = JSON.stringify(answerOptions);
       });
   </script>
   <!-- dragg js -->


   <!-- Question Modal Script -->
   <script>
       document.addEventListener("DOMContentLoaded", function() {
           const modalWrapper = document.getElementById("questionModal"); // your modal wrapper
           const form = document.getElementById("questionForm");
           const questionNameInput = document.getElementById("question_name");
           const categorySelect = document.getElementById("category_id");
           const requiredCheckbox = document.getElementById("required_question");
           const btnText = form.querySelector('button[type="submit"]');

           // Store original form action for creating new questions
           const createAction = "{{ route('admin.checklist-management.rental-ready.questions.store') }}";

           // Handle Edit button clicks
           document.querySelectorAll(".edit-question-btn").forEach(btn => {
               btn.addEventListener("click", function() {
                   const questionId = this.dataset.id;
                   const questionName = this.dataset.name;
                   const categoryId = this.dataset.category;
                   const required = this.dataset.required === '1';
                   const options = JSON.parse(this.dataset.options || '[]');
                   const updateRoute = this.dataset.route;

                   // Prefill form inputs
                   questionNameInput.value = questionName;
                   categorySelect.value = categoryId;
                   requiredCheckbox.checked = required;

                   // Set answerOptions global variable and render
                   answerOptions = options.map(opt => ({
                       id: opt.id,
                       text: opt.text,
                       status: opt.status,
                       index_number: opt.index_number
                   }));

                   nextId = answerOptions.length + 1;
                   renderOptions();

                   // Switch form action to update route
                   form.setAttribute("action", updateRoute);

                   // Add hidden _method=PUT
                   let methodField = form.querySelector("input[name='_method']");
                   if (!methodField) {
                       methodField = document.createElement("input");
                       methodField.type = "hidden";
                       methodField.name = "_method";
                       form.appendChild(methodField);
                   }
                   methodField.value = "PUT";

                   // Change submit button text
                   btnText.textContent = "Update Question";

                   // Show modal
                   modalWrapper.classList.remove("hidden");
               });
           });

           // Reset for Create
           window.openQuestionModal = function() {
               form.setAttribute("action", createAction);

               let methodField = form.querySelector("input[name='_method']");
               if (methodField) methodField.remove();

               questionNameInput.value = "";
               categorySelect.value = "";
               requiredCheckbox.checked = true;

               // Reset options
               // answerOptions = [];
               // nextId = 1;
               answerOptions = [{
                   id: 1,
                   text: 'Inspection Required',
                   status: 'Maint. Hold'
               }];
               nextId = 2;
               renderOptions();

               btnText.textContent = "Save Question";
               modalWrapper.classList.remove("hidden");
           };

           window.closeQuestionModal = function() {
               modalWrapper.classList.add("hidden");
           };
       });
   </script>




   <!-- delete-category -->
   <script>
       document.addEventListener('DOMContentLoaded', function() {
           document.querySelectorAll('.delete-question-form').forEach(function(form) {
               form.addEventListener('submit', function(e) {
                   e.preventDefault(); // stop auto submit

                   const questionName = form.getAttribute('data-question-name') || 'this question';

                   window.showConfirm(
                       `Delete "${questionName}"? This action cannot be undone!`,
                       'Delete question'
                   ).then((result) => {
                       if (result.isConfirmed) {
                           form.submit();
                       }
                   });
               });
           });
       });
   </script>
   <!-- delete-category -->


   <!-- filter  -->

   <script>
       document.addEventListener("DOMContentLoaded", function() {
           const searchInput = document.querySelector('input[placeholder="Search by question name..."]');
           const categorySelect = document.querySelector('select');
           const cards = document.querySelectorAll(".question-card");
           const wrapper = document.getElementById("cardsWrapper");

           function filterCards() {
               // Add loading effect
               wrapper.classList.add('opacity-50', 'pointer-events-none');

               const searchText = searchInput.value.toLowerCase();
               const selectedCategory = categorySelect.value;

               setTimeout(() => {
                   cards.forEach(card => {
                       const title = card.querySelector("h3").textContent.toLowerCase();
                       const cardCategoryId = card.getAttribute("data-category-id");

                       let matchesSearch = !searchText || title.includes(searchText);
                       let matchesCategory = (selectedCategory === "All Categories" || selectedCategory === "") ||
                           cardCategoryId === selectedCategory;

                       if (matchesSearch && matchesCategory) {
                           card.style.display = "block";
                       } else {
                           card.style.display = "none";
                       }
                   });

                   // Remove loading effect
                   wrapper.classList.remove('opacity-50', 'pointer-events-none');
               }, 150); // simulate small delay for UX
           }

           searchInput.addEventListener("input", filterCards);
           categorySelect.addEventListener("change", filterCards);
       });
   </script>


   <!-- filter  -->

   <script>
       document.addEventListener("DOMContentLoaded", function() {
           const wrapper = document.getElementById("cardsWrapper");
           const cards = Array.from(wrapper.querySelectorAll(".question-card"));

           // Sort cards alphabetically by question title (h3)
           cards.sort((a, b) => {
               const nameA = a.querySelector("h3").textContent.trim().toLowerCase();
               const nameB = b.querySelector("h3").textContent.trim().toLowerCase();
               return nameA.localeCompare(nameB);
           });

           // Re-append sorted cards into wrapper
           cards.forEach(card => wrapper.appendChild(card));
       });
   </script>



   @endpush
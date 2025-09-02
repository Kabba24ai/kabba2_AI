   <!-- Inner Tab Contents -->
   <div id="questionssub" class="tab-content" data-tab-group="inner">
       <div class="space-y-6 p-4" id="questionWrapper">
           <!-- Header -->
           <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-6">
    <div class="space-y-1">
        <h2 class="text-xl font-semibold text-gray-900">Customer Questions</h2>
        <p class="text-gray-600">
            Manage delivery/return questions with cost calculation (Return Value - Delivery Value = Customer Charge)
        </p>
    </div>

    <a href="javascript:void(0)" onclick="openQuestionModal()"
   class="inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-purple-600 hover:bg-purple-700 px-5 py-2 text-sm font-medium text-white shadow focus:outline-none focus:ring-2 focus:ring-purple-400 mt-2 sm:mt-0">
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
                           @foreach ($customerAdminCategory as $category)
                           <option value="{{ $category->id }}"> {{ $category->category_name }} </option>
                           @endforeach

                           <!-- <option>Engine</option> -->
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
           <!-- Wrapper around all cards -->
           <div id="cardsWrapper">
               @foreach($customerAdminCategory as $category)
               @foreach($category->questions as $question)
               <!-- Sample Question Card -->
               <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm question-card mb-4" data-required="{{ $question->required_question }}" data-category-id="{{ $category->id }}">
                   <!-- Header -->
                   <div class="flex flex-col md:flex-row md:items-center md:justify-between">
    {{-- Left: Question Title and Category --}}
    <div>
        <div class="flex items-center gap-2">
            <h3 class="text-base font-semibold text-gray-900">{{ $question->question_name }}</h3>

            @if($question->required_question)
                <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full font-medium">Required</span>
            @endif
        </div>
        <p class="text-sm text-gray-600 mt-1">Category: {{ $category->category_name }}</p>
    </div>

    {{-- Right: Buttons --}}
    <div class="flex items-center gap-4 mt-3 md:mt-0 text-gray-600 text-sm" data-count="{{ $question->answers->count() }}">
        {{-- Toggle Answers --}}
        <button class="toggle-answers text-blue-600 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span>Show Questions ({{ $question->answers->count() }})</span>
        </button>

        {{-- Edit Button --}}
        @php
            $options = $question->answers->map(function($answer) {
                return [
                    "id" => $answer->id,
                    "text" => $answer->answer_name,
                    "status" => $answer->type,
                    "index_number" => $answer->index_number,
                ];
            })->values();

            $optionsJson = $options->toJson();
        @endphp

        <button class="edit-question-btn text-green-600 hover:text-green-800"
            data-id="{{ $question->id }}"
            data-name="{{ $question->question_name }}"
            data-category="{{ $question->category_id }}"
            data-required="{{ $question->required_question ? 1 : 0 }}"
            data-options='{{ $optionsJson }}'
            data-route="{{ route('admin.checklist-management.customer-admin.questions.update', $question->id) }}"
            title="Edit">
            <x-heroicon-o-pencil class="w-5 h-5" />
        </button>

        {{-- Delete Button --}}
        <form action="{{ route('admin.checklist-management.customer-admin.questions.delete', $question->unique_id) }}"
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

{{-- ✅ Delivery and Return Texts (new line) --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div class="bg-blue-50 p-3 rounded-lg">
        <p class="flex items-center gap-2 text-sm font-medium text-blue-900">
    <svg class="w-5 h-5 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M19.5 17.5C19.5 18.8807 18.3807 20 17 20C15.6193 20 14.5 18.8807 14.5 17.5C14.5 16.1193 15.6193 15 17 15C18.3807 15 19.5 16.1193 19.5 17.5Z" stroke="currentColor" />
        <path d="M9.5 17.5C9.5 18.8807 8.38071 20 7 20C5.61929 20 4.5 18.8807 4.5 17.5C4.5 16.1193 5.61929 15 7 15C8.38071 15 9.5 16.1193 9.5 17.5Z" stroke="currentColor" />
        <path d="M14.5 17.5H9.5M19.5 17.5H20.2632C20.4831 17.5 20.5931 17.5 20.6855 17.4885C21.3669 17.4036 21.9036 16.8669 21.9885 16.1855C22 16.0931 22 15.9831 22 15.7632V13C22 9.41015 19.0899 6.5 15.5 6.5M15 15.5V7C15 5.58579 15 4.87868 14.5607 4.43934C14.1213 4 13.4142 4 12 4H5C3.58579 4 2.87868 4 2.43934 4.43934C2 4.87868 2 5.58579 2 7V15C2 15.9346 2 16.4019 2.20096 16.75C2.33261 16.978 2.52197 17.1674 2.75 17.299C3.09808 17.5 3.56538 17.5 4.5 17.5" stroke="currentColor" />
        <path d="M9.32653 12L10.8131 10.8258C11.6044 10.2008 12 9.88833 12 9.5M9.32653 7L10.8131 8.17417C11.6044 8.79917 12 9.11168 12 9.5M12 9.5L5 9.5" stroke="currentColor" />
    </svg>
    Delivery Text:
</p>

        <p class="text-sm text-blue-700">{{ $question->question_delivery_text }}</p>
    </div>
    <div class="bg-green-50 p-3 rounded-lg">
        <p class="flex items-center gap-2 text-sm font-medium text-blue-900">
    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" color="currentColor"><path d="M17 20C18.1046 20 19 19.1046 19 18C19 16.8954 18.1046 16 17 16C15.8954 16 15 16.8954 15 18C15 19.1046 15.8954 20 17 20Z" stroke="currentColor"></path><path d="M7 20C8.10457 20 9 19.1046 9 18C9 16.8954 8.10457 16 7 16C5.89543 16 5 16.8954 5 18C5 19.1046 5.89543 20 7 20Z" stroke="currentColor"></path><path d="M19 11H22V13C22 15.357 22 16.5355 21.2678 17.2678C20.7809 17.7546 20.0967 17.9178 19 17.9724M5 17.9724C3.90328 17.9178 3.2191 17.7546 2.73223 17.2678C2 16.5355 2 15.357 2 13V9C2 6.64298 2 5.46447 2.73223 4.73223C3.46447 4 4.64298 4 7 4H10.3C11.4168 4 11.9752 4 12.4271 4.14683C13.3404 4.44358 14.0564 5.15964 14.3532 6.07295C14.5 6.52485 14.5 7.08323 14.5 8.2C14.5 9.42079 14.5 10.0312 14.1657 10.444C14.0998 10.5254 14.0254 10.5998 13.944 10.6657C13.5312 11 12.9208 11 11.7 11H8M15 18H9" stroke="currentColor"></path><path d="M14.5 6H16.3212C17.7766 6 18.5042 6 19.0964 6.35371C19.6886 6.70742 20.0336 7.34811 20.7236 8.6295L22 11" stroke="currentColor"></path><path d="M10 13C10 13 9.3279 12.4436 8.73729 11.9161C8.31975 11.5803 8 11.2926 8 11.0048C8 10.7498 8.24949 10.5128 8.6558 10.1415C9.23188 9.66187 10 9 10 9" stroke="currentColor"></path></svg>
    Return Text:
</p>

        <p class="text-sm text-green-700">{{ $question->question_return_text }}</p>
    </div>
</div>


                   <!-- Answers (initially hidden) -->
                   <div class="answers hidden mt-5 border-t pt-5 space-y-2">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($question->answers as $index => $answer)

                        @php 
                        /*
                        echo "<pre>";
                            echo "JIG".$answer->unique_id;
                        print_r($answer)
                        */
                        @endphp
    <div>
        <h5 class="font-medium text-blue-900 mb-3 flex items-center gap-2">
            <p class="flex items-center gap-2 text-sm font-medium text-blue-900">
    <svg class="w-6 h-6 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M19.5 17.5C19.5 18.8807 18.3807 20 17 20C15.6193 20 14.5 18.8807 14.5 17.5C14.5 16.1193 15.6193 15 17 15C18.3807 15 19.5 16.1193 19.5 17.5Z" stroke="currentColor" />
        <path d="M9.5 17.5C9.5 18.8807 8.38071 20 7 20C5.61929 20 4.5 18.8807 4.5 17.5C4.5 16.1193 5.61929 15 7 15C8.38071 15 9.5 16.1193 9.5 17.5Z" stroke="currentColor" />
        <path d="M14.5 17.5H9.5M19.5 17.5H20.2632C20.4831 17.5 20.5931 17.5 20.6855 17.4885C21.3669 17.4036 21.9036 16.8669 21.9885 16.1855C22 16.0931 22 15.9831 22 15.7632V13C22 9.41015 19.0899 6.5 15.5 6.5M15 15.5V7C15 5.58579 15 4.87868 14.5607 4.43934C14.1213 4 13.4142 4 12 4H5C3.58579 4 2.87868 4 2.43934 4.43934C2 4.87868 2 5.58579 2 7V15C2 15.9346 2 16.4019 2.20096 16.75C2.33261 16.978 2.52197 17.1674 2.75 17.299C3.09808 17.5 3.56538 17.5 4.5 17.5" stroke="currentColor" />
        <path d="M9.32653 12L10.8131 10.8258C11.6044 10.2008 12 9.88833 12 9.5M9.32653 7L10.8131 8.17417C11.6044 8.79917 12 9.11168 12 9.5M12 9.5L5 9.5" stroke="currentColor" />
    </svg>Delivery Answer Options:
    
</p>

            </h5>
        <div class="space-y-2">
            
            <div class="flex items-center gap-2 p-3 bg-blue-50 rounded-lg border border-blue-200"><span class="w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-xs font-medium">{{ $index +1 }}</span><span class="flex-1 text-sm font-medium">{{ $answer->answer_delivery_text }}</span>
                <div class="flex items-center gap-1 bg-white px-2 py-1 rounded border">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-3 h-3 text-green-600">
                        <line x1="12" x2="12" y1="2" y2="22"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg><span class="text-sm font-medium text-green-600">{{ $answer->delivery_amt }}</span></div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-link w-4 h-4 text-blue-600" title="Synced with return answer">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                </svg>
            </div>
        </div>
    </div>
    <div>
        <h5 class="font-medium text-green-900 mb-3 flex items-center gap-2">
            <p class="flex items-center gap-2 text-sm font-medium text-blue-900">
    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" color="currentColor"><path d="M17 20C18.1046 20 19 19.1046 19 18C19 16.8954 18.1046 16 17 16C15.8954 16 15 16.8954 15 18C15 19.1046 15.8954 20 17 20Z" stroke="currentColor"></path><path d="M7 20C8.10457 20 9 19.1046 9 18C9 16.8954 8.10457 16 7 16C5.89543 16 5 16.8954 5 18C5 19.1046 5.89543 20 7 20Z" stroke="currentColor"></path><path d="M19 11H22V13C22 15.357 22 16.5355 21.2678 17.2678C20.7809 17.7546 20.0967 17.9178 19 17.9724M5 17.9724C3.90328 17.9178 3.2191 17.7546 2.73223 17.2678C2 16.5355 2 15.357 2 13V9C2 6.64298 2 5.46447 2.73223 4.73223C3.46447 4 4.64298 4 7 4H10.3C11.4168 4 11.9752 4 12.4271 4.14683C13.3404 4.44358 14.0564 5.15964 14.3532 6.07295C14.5 6.52485 14.5 7.08323 14.5 8.2C14.5 9.42079 14.5 10.0312 14.1657 10.444C14.0998 10.5254 14.0254 10.5998 13.944 10.6657C13.5312 11 12.9208 11 11.7 11H8M15 18H9" stroke="currentColor"></path><path d="M14.5 6H16.3212C17.7766 6 18.5042 6 19.0964 6.35371C19.6886 6.70742 20.0336 7.34811 20.7236 8.6295L22 11" stroke="currentColor"></path><path d="M10 13C10 13 9.3279 12.4436 8.73729 11.9161C8.31975 11.5803 8 11.2926 8 11.0048C8 10.7498 8.24949 10.5128 8.6558 10.1415C9.23188 9.66187 10 9 10 9" stroke="currentColor"></path></svg>
    Return Answer Options:
</p>
            </h5>
        <div class="space-y-2">
            
            <div class="flex items-center gap-2 p-3 bg-green-50 rounded-lg border border-green-200"><span class="w-6 h-6 bg-green-200 rounded-full flex items-center justify-center text-xs font-medium">{{ $index+1 }}</span><span class="flex-1 text-sm font-medium">{{ $answer->answer_return_text }}</span>
                <div class="flex items-center gap-1 bg-white px-2 py-1 rounded border">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-3 h-3 text-green-600">
                        <line x1="12" x2="12" y1="2" y2="22"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg><span class="text-sm font-medium text-red-600">{{ $answer->return_amt }}</span></div>
                <div class="flex items-center gap-1 bg-yellow-50 px-2 py-1 rounded text-xs border border-yellow-200"><span class="text-gray-600">Charge:</span><span class="font-medium text-red-600">${{ ($answer->return_amt)-($answer->delivery_amt) }}</span></div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" class="lucide lucide-link w-4 h-4 text-green-600" title="Synced with delivery answer">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                </svg>
            </div>
        </div>
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
           <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-4xl flex flex-col max-h-full overflow-hidden border border-gray-200">
               <div class="flex justify-between items-center px-6 pt-4">
                   <h3 class="text-lg font-semibold text-gray-800">New Question</h3>
                   <button onclick="closeQuestionModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
               </div>

               <!-- Scrollable Content -->
               <div class=" overflow-y-auto max-h-[70vh]">
                   <!-- {{-- Form --}} -->
                   {{ html()->form('POST', route('admin.checklist-management.customer-admin.questions.store'))
                ->id('questionForm')
                ->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'flex flex-col flex-1'
                ])
                ->acceptsFiles()
                ->open() }}


                   <input type="hidden" name="options" id="optionsInput">


                   <div class="max-w-4xl px-6 py-4 space-y-6 overflow-y-auto">

    <!-- Row 1: Question Name and Category -->
    <div class="flex flex-col md:flex-row gap-4">
        <!-- Question Name -->
        <div class="w-full md:w-1/2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Question Name *</label>
            {!! html()->text('question_name')
            ->class('w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500')
            ->attributes(['id' => 'question_name', 'placeholder' => 'Enter Question name'])
            ->required() !!}
        </div>

        <!-- Question Category -->
        <div class="w-full md:w-1/2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Question Category *</label>
            {!! html()->select(
            'category_id',
            ['' => '-- Select Category --'] + $customerAdminCategory->pluck('category_name', 'id')->toArray()
            )
            ->class('w-full text-sm border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500')
            ->attributes(['id' => 'category_id'])
            ->required()
            !!}
        </div>
    </div>


    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <div class="flex items-center gap-2 mb-4">
                        <input type="checkbox" id="syncTexts" name="syncTexts" checked="" onchange="toggleTextSync()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        
                       
                        
                        <label for="syncTexts" class="text-sm font-medium text-gray-700 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            Sync Delivery &amp; Return Text
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                             <label class="block text-sm font-medium text-gray-700 mb-2">
                                <p class="flex items-center gap-2 text-sm font-medium text-blue-900">
    <svg class="w-5 h-5 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M19.5 17.5C19.5 18.8807 18.3807 20 17 20C15.6193 20 14.5 18.8807 14.5 17.5C14.5 16.1193 15.6193 15 17 15C18.3807 15 19.5 16.1193 19.5 17.5Z" stroke="currentColor" />
        <path d="M9.5 17.5C9.5 18.8807 8.38071 20 7 20C5.61929 20 4.5 18.8807 4.5 17.5C4.5 16.1193 5.61929 15 7 15C8.38071 15 9.5 16.1193 9.5 17.5Z" stroke="currentColor" />
        <path d="M14.5 17.5H9.5M19.5 17.5H20.2632C20.4831 17.5 20.5931 17.5 20.6855 17.4885C21.3669 17.4036 21.9036 16.8669 21.9885 16.1855C22 16.0931 22 15.9831 22 15.7632V13C22 9.41015 19.0899 6.5 15.5 6.5M15 15.5V7C15 5.58579 15 4.87868 14.5607 4.43934C14.1213 4 13.4142 4 12 4H5C3.58579 4 2.87868 4 2.43934 4.43934C2 4.87868 2 5.58579 2 7V15C2 15.9346 2 16.4019 2.20096 16.75C2.33261 16.978 2.52197 17.1674 2.75 17.299C3.09808 17.5 3.56538 17.5 4.5 17.5" stroke="currentColor" />
        <path d="M9.32653 12L10.8131 10.8258C11.6044 10.2008 12 9.88833 12 9.5M9.32653 7L10.8131 8.17417C11.6044 8.79917 12 9.11168 12 9.5M12 9.5L5 9.5" stroke="currentColor" />
    </svg>
    Delivery Text:
</p>
</label>
                        {!! html()->text('question_delivery_text')
    ->class('w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent')
    ->attributes([
        'id' => 'question_delivery_text',
        'placeholder' => 'Delivery Text',
        'oninput' => 'handleDeliveryTextChange()'
    ])
    ->required() !!}

                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <p class="flex items-center gap-2 text-sm font-medium text-blue-900">
    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" color="currentColor"><path d="M17 20C18.1046 20 19 19.1046 19 18C19 16.8954 18.1046 16 17 16C15.8954 16 15 16.8954 15 18C15 19.1046 15.8954 20 17 20Z" stroke="currentColor"></path><path d="M7 20C8.10457 20 9 19.1046 9 18C9 16.8954 8.10457 16 7 16C5.89543 16 5 16.8954 5 18C5 19.1046 5.89543 20 7 20Z" stroke="currentColor"></path><path d="M19 11H22V13C22 15.357 22 16.5355 21.2678 17.2678C20.7809 17.7546 20.0967 17.9178 19 17.9724M5 17.9724C3.90328 17.9178 3.2191 17.7546 2.73223 17.2678C2 16.5355 2 15.357 2 13V9C2 6.64298 2 5.46447 2.73223 4.73223C3.46447 4 4.64298 4 7 4H10.3C11.4168 4 11.9752 4 12.4271 4.14683C13.3404 4.44358 14.0564 5.15964 14.3532 6.07295C14.5 6.52485 14.5 7.08323 14.5 8.2C14.5 9.42079 14.5 10.0312 14.1657 10.444C14.0998 10.5254 14.0254 10.5998 13.944 10.6657C13.5312 11 12.9208 11 11.7 11H8M15 18H9" stroke="currentColor"></path><path d="M14.5 6H16.3212C17.7766 6 18.5042 6 19.0964 6.35371C19.6886 6.70742 20.0336 7.34811 20.7236 8.6295L22 11" stroke="currentColor"></path><path d="M10 13C10 13 9.3279 12.4436 8.73729 11.9161C8.31975 11.5803 8 11.2926 8 11.0048C8 10.7498 8.24949 10.5128 8.6558 10.1415C9.23188 9.66187 10 9 10 9" stroke="currentColor"></path></svg>
    Return Text:
</p>
                            </label>

                            {!! html()->text('question_return_text')
    ->class('w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent')
    ->attributes([
        'id' => 'question_return_text',
        'placeholder' => 'Return Text',
    ])
    ->required() !!}
                            
                        </div>
                    </div>
                </div>

    <!-- Required Question Checkbox -->
    <div>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            {!! html()->checkbox('required_question', false, 1)
            ->class('form-checkbox h-4 w-4 text-blue-600') !!}
            Required Question
        </label>
    </div>

    <!-- Answer Options -->
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">Answer Options <span class="text-xs text-gray-400 font-normal">(Drag to reorder)</span></span>
            <button type="button" onclick="addOption()" class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-4 h-4"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>Add Answer Pair</button>
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
<script>
    let answerOptions = [{
        id: 1,
        delivery_text: '',
        return_text: '',
        delivery_amt: 0,
        return_amt: 0,
        syncEnabled: true
    }];
    let nextId = 2;

    function openQuestionModal() {
        document.getElementById('questionModal').classList.remove('hidden');
        renderOptions();
    }

    function closeQuestionModal() {
        document.getElementById('questionModal').classList.add('hidden');
    }

    function renderOptions() {
        const container = document.getElementById('sortable-list');
        container.innerHTML = '';

        answerOptions.forEach((option, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'border border-gray-200 rounded-lg p-4 bg-white';
            wrapper.setAttribute('data-id', option.id);

            // Calculate charge difference
            const chargeDifference = option.return_amt - option.delivery_amt;
            const chargeColorClass = chargeDifference > 0 ? 'text-red-600' : 'text-green-600';
            const chargeSign = chargeDifference > 0 ? '+' : '';

            wrapper.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700">Answer ${index + 1}</span>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="sync-${index}" class="sync-toggle rounded border-gray-300 text-blue-600 focus:ring-blue-500" ${option.syncEnabled ? 'checked' : ''}>
                            <label for="sync-${index}" class="text-xs text-gray-600 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-link w-3 h-3">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                </svg>Sync
                            </label>
                        </div>
                        <div class="flex items-center gap-1 bg-yellow-50 px-2 py-1 rounded text-xs border border-yellow-200">
                            <span class="text-gray-600">Charge:</span>
                            <span class="font-medium charge-display ${chargeColorClass}">${chargeSign}$${Math.abs(chargeDifference).toFixed(2)}</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="border border-blue-200 rounded-lg p-3 bg-blue-50">
                        <label class="block text-sm font-medium text-blue-900 mb-2">
                            <p class="flex items-center gap-2 text-sm font-medium text-blue-900">
    <svg class="w-5 h-5 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M19.5 17.5C19.5 18.8807 18.3807 20 17 20C15.6193 20 14.5 18.8807 14.5 17.5C14.5 16.1193 15.6193 15 17 15C18.3807 15 19.5 16.1193 19.5 17.5Z" stroke="currentColor" />
        <path d="M9.5 17.5C9.5 18.8807 8.38071 20 7 20C5.61929 20 4.5 18.8807 4.5 17.5C4.5 16.1193 5.61929 15 7 15C8.38071 15 9.5 16.1193 9.5 17.5Z" stroke="currentColor" />
        <path d="M14.5 17.5H9.5M19.5 17.5H20.2632C20.4831 17.5 20.5931 17.5 20.6855 17.4885C21.3669 17.4036 21.9036 16.8669 21.9885 16.1855C22 16.0931 22 15.9831 22 15.7632V13C22 9.41015 19.0899 6.5 15.5 6.5M15 15.5V7C15 5.58579 15 4.87868 14.5607 4.43934C14.1213 4 13.4142 4 12 4H5C3.58579 4 2.87868 4 2.43934 4.43934C2 4.87868 2 5.58579 2 7V15C2 15.9346 2 16.4019 2.20096 16.75C2.33261 16.978 2.52197 17.1674 2.75 17.299C3.09808 17.5 3.56538 17.5 4.5 17.5" stroke="currentColor" />
        <path d="M9.32653 12L10.8131 10.8258C11.6044 10.2008 12 9.88833 12 9.5M9.32653 7L10.8131 8.17417C11.6044 8.79917 12 9.11168 12 9.5M12 9.5L5 9.5" stroke="currentColor" />
    </svg>
    Delivery Text:
</p>
                            </label>
                        <div class="flex items-center gap-2">
                            <input type="text" placeholder="Answer description..." 
                                class="delivery_text flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                                value="${option.delivery_text}" 
                                oninput="updateDeliveryText(${index}, this.value)" required>
                            <div class="flex items-center gap-1 bg-white px-2 py-2 rounded-md border border-gray-300">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4 text-green-600">
                                    <line x1="12" x2="12" y1="2" y2="22"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                <input type="number" placeholder="0" class="delivery_amt w-16 border-0 focus:ring-0 p-0 text-sm" min="0" step="0.01" 
                                    value="${option.delivery_amt}" 
                                    oninput="updateDeliveryAmt(${index}, this.value)">
                            </div>
                        </div>
                    </div>
                    <div class="border border-green-200 rounded-lg p-3 bg-green-50">
                        <label class="block text-sm font-medium text-green-900 mb-2">
                            
                            <p class="flex items-center gap-2 text-sm font-medium text-blue-900">
    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" color="currentColor"><path d="M17 20C18.1046 20 19 19.1046 19 18C19 16.8954 18.1046 16 17 16C15.8954 16 15 16.8954 15 18C15 19.1046 15.8954 20 17 20Z" stroke="currentColor"></path><path d="M7 20C8.10457 20 9 19.1046 9 18C9 16.8954 8.10457 16 7 16C5.89543 16 5 16.8954 5 18C5 19.1046 5.89543 20 7 20Z" stroke="currentColor"></path><path d="M19 11H22V13C22 15.357 22 16.5355 21.2678 17.2678C20.7809 17.7546 20.0967 17.9178 19 17.9724M5 17.9724C3.90328 17.9178 3.2191 17.7546 2.73223 17.2678C2 16.5355 2 15.357 2 13V9C2 6.64298 2 5.46447 2.73223 4.73223C3.46447 4 4.64298 4 7 4H10.3C11.4168 4 11.9752 4 12.4271 4.14683C13.3404 4.44358 14.0564 5.15964 14.3532 6.07295C14.5 6.52485 14.5 7.08323 14.5 8.2C14.5 9.42079 14.5 10.0312 14.1657 10.444C14.0998 10.5254 14.0254 10.5998 13.944 10.6657C13.5312 11 12.9208 11 11.7 11H8M15 18H9" stroke="currentColor"></path><path d="M14.5 6H16.3212C17.7766 6 18.5042 6 19.0964 6.35371C19.6886 6.70742 20.0336 7.34811 20.7236 8.6295L22 11" stroke="currentColor"></path><path d="M10 13C10 13 9.3279 12.4436 8.73729 11.9161C8.31975 11.5803 8 11.2926 8 11.0048C8 10.7498 8.24949 10.5128 8.6558 10.1415C9.23188 9.66187 10 9 10 9" stroke="currentColor"></path></svg>
    Return <span class="text-xs">${option.syncEnabled ? '(Synced)' : ''}</span>
</p>
</label>
                        <div class="flex items-center gap-2">
                            <input type="text" placeholder="Answer description..." 
                                class="return_text flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                                value="${option.syncEnabled ? option.delivery_text : option.return_text}" 
                                ${option.syncEnabled ? 'disabled' : ''} 
                                oninput="updateReturnText(${index}, this.value)" required>
                            <div class="flex items-center gap-1 bg-white px-2 py-2 rounded-md border border-gray-300">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4 text-green-600">
                                    <line x1="12" x2="12" y1="2" y2="22"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                <input type="number" placeholder="0" class="return_amt w-16 border-0 focus:ring-0 p-0 text-sm" min="0" step="0.01" 
                                    value="${option.syncEnabled ? option.delivery_amt : option.return_amt}" 
                                    oninput="updateReturnAmt(${index}, this.value)">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-4">
                    <div class="flex items-center gap-2">
                        <span class="drag-handle w-7 h-7 flex items-center justify-center rounded-full text-gray-900 cursor-move">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6h.01M10 10h.01M10 14h.01M14 6h.01M14 10h.01M14 14h.01" />
                            </svg>
                        </span>
                        <span class="text-xs text-gray-500">Drag to reorder</span>
                    </div>
                    <button type="button" onclick="removeOption(${index})" class="text-red-600 rounded-full w-8 h-8 flex items-center justify-center hover:text-red-800" title="Delete">
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </button>
                </div>
            `;

            container.appendChild(wrapper);
            
            // Add event listener for sync toggle
            const syncToggle = wrapper.querySelector('.sync-toggle');
            syncToggle.addEventListener('change', function() {
                updateSyncStatus(index, this.checked);
            });
        });

        // Re-init Sortable
        if (container.children.length > 0) {
            Sortable.create(container, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function(evt) {
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
            delivery_text: '',
            return_text: '',
            delivery_amt: 0,
            return_amt: 0,
            syncEnabled: true
        });
        renderOptions();
    }

    function removeOption(index) {
        answerOptions.splice(index, 1);
        renderOptions();
    }

    function updateDeliveryText(index, value) {
        answerOptions[index].delivery_text = value;
        if (answerOptions[index].syncEnabled) {
            // Update return text if sync is enabled
            const returnInputs = document.querySelectorAll('.return_text');
            if (returnInputs[index]) {
                returnInputs[index].value = value;
            }
            answerOptions[index].return_text = value;
        }
    }

    function updateDeliveryAmt(index, value) {
        answerOptions[index].delivery_amt = parseFloat(value) || 0;
        if (answerOptions[index].syncEnabled) {
            // Update return amount if sync is enabled
            const returnAmtInputs = document.querySelectorAll('.return_amt');
            if (returnAmtInputs[index]) {
                returnAmtInputs[index].value = value;
            }
            answerOptions[index].return_amt = parseFloat(value) || 0;
        }
        updateChargeDisplay(index);
    }

    function updateReturnText(index, value) {
        answerOptions[index].return_text = value;
    }

    function updateReturnAmt(index, value) {
        answerOptions[index].return_amt = parseFloat(value) || 0;
        updateChargeDisplay(index);
    }

    function updateChargeDisplay(index) {
        const chargeDisplays = document.querySelectorAll('.charge-display');
        const chargeDifference = answerOptions[index].return_amt - answerOptions[index].delivery_amt;
        const chargeColorClass = chargeDifference > 0 ? 'text-red-600' : 'text-green-600';
        const chargeSign = chargeDifference > 0 ? '+' : '';
        
        if (chargeDisplays[index]) {
            chargeDisplays[index].textContent = `${chargeSign}$${Math.abs(chargeDifference).toFixed(2)}`;
            chargeDisplays[index].className = `font-medium charge-display ${chargeColorClass}`;
        }
    }

    function updateSyncStatus(index, isEnabled) {
        answerOptions[index].syncEnabled = isEnabled;
        
        // Update UI based on sync status
        const returnInputs = document.querySelectorAll('.return_text');
        const returnAmtInputs = document.querySelectorAll('.return_amt');
        const returnLabels = document.querySelectorAll('.border-green-200 .text-green-900');
        
        if (returnInputs[index]) {
            returnInputs[index].disabled = isEnabled;
            if (isEnabled) {
                returnInputs[index].value = answerOptions[index].delivery_text;
                answerOptions[index].return_text = answerOptions[index].delivery_text;
            }
        }
        
        if (returnAmtInputs[index]) {
            // Return amount input should never be disabled, only the text input
            if (isEnabled) {
                returnAmtInputs[index].value = answerOptions[index].delivery_amt;
                answerOptions[index].return_amt = answerOptions[index].delivery_amt;
            }
        }
        
        if (returnLabels[index]) {
            const span = returnLabels[index].querySelector('span');
            if (span) {
                span.textContent = isEnabled ? '(Synced)' : '';
            }
        }
        
        updateChargeDisplay(index);
        renderOptions(); // Re-render to update all UI elements
    }

    // Before submitting form, sync options into hidden input
    document.getElementById('questionForm').addEventListener('submit', function(e) {
        // Format the data for submission
        const formattedOptions = answerOptions.map(option => ({
            id: option.id,
            delivery_text: option.delivery_text,
            return_text: option.return_text,
            delivery_amt: option.delivery_amt,
            return_amt: option.return_amt,
            syncEnabled: option.syncEnabled
        }));
        
        document.getElementById('optionsInput').value = JSON.stringify(formattedOptions);
    });
</script>

<script>
    function toggleTextSync() {
        const isChecked = document.getElementById('syncTexts').checked;
        const question_delivery_text = document.getElementById('question_delivery_text');
        const question_return_text = document.getElementById('question_return_text');

        if (isChecked) {
            question_return_text.value = question_delivery_text.value;
            question_return_text.disabled = true;
        } else {
            question_return_text.disabled = false;
        }
    }

    function handleDeliveryTextChange() {
        const isChecked = document.getElementById('syncTexts').checked;
        const question_delivery_text = document.getElementById('question_delivery_text');
        const question_return_text = document.getElementById('question_return_text');

        if (isChecked) {
            question_return_text.value = question_delivery_text.value;
        }
    }

    // Optional: Sync returnText on initial load if checkbox is checked
    document.addEventListener('DOMContentLoaded', function () {
        toggleTextSync();
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

         
           cards.forEach(card => wrapper.appendChild(card));
       });
   </script>



   @endpush

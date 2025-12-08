<div id="templates" class="tab-content hidden" data-tab-group="main">

            <div class="space-y-6" id="questionWrappertemp">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Customer Checklist Templates
</h2>
                        <p class="text-gray-600">Create delivery/return checklist templates for different equipment categories</p>
                    </div>
                    <a href="javascript:void(0)" id="openTemplatesModal"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 mt-3">
                        + New Template
                    </a>
                </div>

                <!-- Top Filters -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 space-y-4 shadow-sm mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search Templates</label>
                            <input id="searchTemplatesInput" type="text" placeholder="Search Templates..." class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <!-- Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Template Count</label>
                            <input type="text"  class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ $checklisttemplate->count() }} of {{ $checklisttemplate->count() }} templates" readonly/>
                        </div>

                        <!-- Answer Visibility Toggle -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Questions Visibility</label>
                            <button id="toggleGlobalAnswerstemp"
                            class="w-full font-medium px-4 py-2 text-sm rounded-md flex items-center justify-center gap-2 border border-blue-300 text-blue-600 hover:bg-gray-50 transition">
                                <svg id="icon-showtemp" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="icon-hidetemp" class="w-5 h-5 text-gray-900 hidden" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.042 10.042 0 013.03-4.362M6.873 6.876A9.953 9.953 0 0112 5c4.477 0 8.267 2.943 9.541 7a9.966 9.966 0 01-1.249 2.527M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                </svg>
                                <span id="toggleTexttemp">Show Questions</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="cardsWrapper">
                @foreach ($checklisttemplate as $template)
                  <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm question-cardtemp mb-6" data-required="true">
                      <!-- Header -->
                      <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                          <div>
                              <div class="flex items-center gap-2 mb-2">
                                  <h3 class="text-base font-semibold text-gray-900">{{ $template->template_name }}</h3>
                              <span class="{{ $template->active_template
                                      ? 'bg-green-100 text-green-600'
                                      : 'bg-red-100 text-red-600' }}
                                  text-xs px-2 py-0.5 rounded-full font-medium">
                                  {{ $template->active_template ? 'Active' : 'Inactive' }}
                              </span>
                              </div>
                              <p class="text-sm text-gray-600 mb-1">{{ $template->description ?? 'N/A'}} </p>
                              <p class="text-sm text-gray-600 mb-1">Equipment Category: {{ $template->equipmentCategory->title ?? 'No Category Assigned' }}</p>
                              <p class="text-sm text-gray-600 mt-1">{{ $template->questions->count() }} {{ Str::plural('question', $template->questions->count()) }}</p>
                          </div>

                          <div class="flex items-center gap-4 mt-3 md:mt-0 text-gray-600 text-sm">
                              <button class="toggle-answerstemp text-blue-600 hover:underline flex items-center gap-1">
                                  <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor"
                                      stroke-width="2" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                  </svg>
                                  <span>Show Questions ({{ $template->questions->count() }})</span>
                              </button>
                              <!-- Edit -->
                              @php
$templateQuestions = $template->questions->map(function($q) {
    return [
        "id" => $q->question?->id,
        "text" => $q->question?->question_name ?? '',
        "category" => $q->question?->category?->category_name ?? '',
        "required" => (bool) ($q->question?->required_question ?? false),
        "question_delivery_text" => $q->question?->question_delivery_text ?? '',
        "question_return_text" => $q->question?->question_return_text ?? ''
    ];
});
@endphp
<form
                                    action="{{ route('admin.checklist-management.customer-admin.templates.copy', $template->unique_id) }}"
                                    method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800"
                                        title="Copy this Checklist Master">
                                        <x-heroicon-o-clipboard-document class="w-4 h-4" />
                                    </button>
                                </form>

                              <button
                                class="edit-template-btn text-green-600 hover:text-green-800"
                                title="Edit"
                                data-id="{{ $template->id }}"
                                data-route="{{ route('admin.checklist-management.customer-admin.templates.update', $template->unique_id) }}"
                                data-name="{{ $template->template_name }}"
                                data-description="{{ $template->description }}"
                                data-equipment="{{ $template->equipment_category_id }}"
                                data-active="{{ $template->active_template }}"
                                data-questions='@json($templateQuestions)'>
                                <x-heroicon-o-pencil class="w-5 h-5" />
                              </button>

                              <!-- Delete -->
                              <!-- <button class="text-red-600 hover:text-red-800" title="Delete">
                                  <x-heroicon-o-trash class="w-5 h-5" />
                              </button> -->


                              <!-- Delete -->
                                <!-- Delete -->
<form action="{{ route('admin.checklist-management.customer-admin.templates.delete', $template->unique_id) }}"
      method="POST"
      class="inline delete-template-form"
      data-template-name="{{ $template->template_name }}">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
        <x-heroicon-o-trash class="w-5 h-5" />
    </button>
</form>



                          </div>
                      </div>

                      <!-- Answers (initially hidden) -->
                      <div class="answerstemp hidden mt-5 border-t pt-5 space-y-2">
                          <!-- <div class="border-t border-gray-300 mt-5 mb-5"></div> -->
                              <h4 class="text-sm font-semibold text-gray-800">Questions in Template:</h4>


                            @foreach ($template->questions as $templateQuestion)
                            <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm"><span class="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium">{{ $loop->iteration }}</span>
    <div class="flex-1"><span class="font-medium">{{ $templateQuestion->question->question_name ?? 'No Question' }}</span>
        <div class="grid grid-cols-2 gap-2 mt-1 text-xs">
            <div class="text-blue-600">
              
              <p class="pt-2 flex items-center gap-2 text-xs font-regular text-blue-900">
    <svg class="w-4 h-4 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M19.5 17.5C19.5 18.8807 18.3807 20 17 20C15.6193 20 14.5 18.8807 14.5 17.5C14.5 16.1193 15.6193 15 17 15C18.3807 15 19.5 16.1193 19.5 17.5Z" stroke="currentColor" />
        <path d="M9.5 17.5C9.5 18.8807 8.38071 20 7 20C5.61929 20 4.5 18.8807 4.5 17.5C4.5 16.1193 5.61929 15 7 15C8.38071 15 9.5 16.1193 9.5 17.5Z" stroke="currentColor" />
        <path d="M14.5 17.5H9.5M19.5 17.5H20.2632C20.4831 17.5 20.5931 17.5 20.6855 17.4885C21.3669 17.4036 21.9036 16.8669 21.9885 16.1855C22 16.0931 22 15.9831 22 15.7632V13C22 9.41015 19.0899 6.5 15.5 6.5M15 15.5V7C15 5.58579 15 4.87868 14.5607 4.43934C14.1213 4 13.4142 4 12 4H5C3.58579 4 2.87868 4 2.43934 4.43934C2 4.87868 2 5.58579 2 7V15C2 15.9346 2 16.4019 2.20096 16.75C2.33261 16.978 2.52197 17.1674 2.75 17.299C3.09808 17.5 3.56538 17.5 4.5 17.5" stroke="currentColor" />
        <path d="M9.32653 12L10.8131 10.8258C11.6044 10.2008 12 9.88833 12 9.5M9.32653 7L10.8131 8.17417C11.6044 8.79917 12 9.11168 12 9.5M12 9.5L5 9.5" stroke="currentColor" />
    </svg>
              {{ $templateQuestion->question?->question_delivery_text ?? '' }}

              </p>
            </div>
            <div class="text-green-600">
              <p class="flex items-center gap-2 text-xs font-regular text-green-500">
    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" color="currentColor"><path d="M17 20C18.1046 20 19 19.1046 19 18C19 16.8954 18.1046 16 17 16C15.8954 16 15 16.8954 15 18C15 19.1046 15.8954 20 17 20Z" stroke="currentColor"></path><path d="M7 20C8.10457 20 9 19.1046 9 18C9 16.8954 8.10457 16 7 16C5.89543 16 5 16.8954 5 18C5 19.1046 5.89543 20 7 20Z" stroke="currentColor"></path><path d="M19 11H22V13C22 15.357 22 16.5355 21.2678 17.2678C20.7809 17.7546 20.0967 17.9178 19 17.9724M5 17.9724C3.90328 17.9178 3.2191 17.7546 2.73223 17.2678C2 16.5355 2 15.357 2 13V9C2 6.64298 2 5.46447 2.73223 4.73223C3.46447 4 4.64298 4 7 4H10.3C11.4168 4 11.9752 4 12.4271 4.14683C13.3404 4.44358 14.0564 5.15964 14.3532 6.07295C14.5 6.52485 14.5 7.08323 14.5 8.2C14.5 9.42079 14.5 10.0312 14.1657 10.444C14.0998 10.5254 14.0254 10.5998 13.944 10.6657C13.5312 11 12.9208 11 11.7 11H8M15 18H9" stroke="currentColor"></path><path d="M14.5 6H16.3212C17.7766 6 18.5042 6 19.0964 6.35371C19.6886 6.70742 20.0336 7.34811 20.7236 8.6295L22 11" stroke="currentColor"></path><path d="M10 13C10 13 9.3279 12.4436 8.73729 11.9161C8.31975 11.5803 8 11.2926 8 11.0048C8 10.7498 8.24949 10.5128 8.6558 10.1415C9.23188 9.66187 10 9 10 9" stroke="currentColor"></path></svg>
     {{ $templateQuestion->question?->question_delivery_text ?? '' }}

</p>
</div>
        </div>
    </div>
    
    <span class="text-xs text-gray-500">{{ $templateQuestion->question->category->category_name ?? 'No Category' }}</span>
    
    @if ($templateQuestion->question?->required_question == 1)
                                      <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                                  @else
                                      <span class="text-xs text-gray-500">General</span>
                                  @endif
                                </div>


                          @endforeach



                      </div>
                  </div>
                @endforeach
               </div>
            </div>

        </div>
        <!-- Modal Wrapper -->
<div id="templatesModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">

     {{ html()->form()->attributes([
         'method' => 'POST',
         'id' => 'templateForm',
         'autocomplete' => 'off',
         'data-parsley-validate' => true,
         'class' => 'modal-scrollable w-full mx-auto',
        'action' => route('admin.checklist-management.customer-admin.templates.store'),

     ])->open() }}
     @csrf

     <input type="hidden" name="questions" id="questionsInput">

        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-6xl space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">

            <div class="flex justify-between items-center px-6 pt-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">New Template</h3>
                <button type="button" id="closeModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6 overflow-y-auto">
                <!-- Template Info -->
                <div class="space-y-4 pr-0 md:pr-6 md:border-r">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Template Name *</label>

                        {{ html()->text('template_name')->attributes([
                                'class' => 'w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500',
                                'required' => true,
                                'id' => 'template_name',

                            ])->placeholder('Enter template name')
                        }}

                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                          {{ html()->textarea('description', null)->attributes([
                              'class' => 'w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500',
                              'rows' => 3,
                              'id' => 'temp_description',
                          ])->placeholder('Optional description...') }}
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Equipment Category *</label>
                              {!! html()->select(
                                          'equipment_category',
                                          ['' => '-- Select Category --'] + $equipmentCategories,
                                          old('equipment_category')
                                      )
                                      ->class('w-full border text-sm border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500')
                                      ->attribute('required', true)
                                  !!}
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-2">
                        <!-- <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600" />  -->
                         {!! html()->checkbox('is_active', old('is_active', true))
                            ->class('form-checkbox h-4 w-4 text-blue-600')
                        !!}
                        Active Template
                    </label>
                </div>

                <!-- Available Questions -->
                <div class="space-y-4 pr-0 md:pr-6 md:border-r">
                    <h3 class="text-base font-semibold text-gray-800">Available Questions</h3>
                         <input type="text" id="searchInput" placeholder="Search questions..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">

                          <select id="categoryFilter" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                      <option value="All">All Categories</option>
                      @foreach($customerAdminCategory as $category)
                          <option value="{{ $category->category_name }}">{{ $category->category_name }}</option>
                      @endforeach
                    </select>

                    <div id="available" class="bg-white space-y-2 h-[350px] overflow-y-auto bg-gray-50 rounded-md"></div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        Template Questions (<span id="templateCount">0</span>)
                    </h3>
                    <div id="template" class="space-y-2 h-450 overflow-y-auto bg-white border-2 border-dashed border-gray-300 p-3 rounded-md"></div>
                </div>


            </div>

            <div class="flex justify-end gap-2 px-6 pb-4">
                <button type="button" id="cancelBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">Cancel</button>
                <button type="submit" id="submitTemplatesBtn" class="px-4 py-2 text-sm rounded bg-indigo-600 hover:bg-indigo-700 text-white">Save Template</button>
            </div>


        </div>
    {{ html()->form()->close() }}



</div>



@push('js')



<!-- tempplet toggal  -->

<script>
document.addEventListener('DOMContentLoaded', function () {
  let globalVisible = false;

  const globalToggleBtn = document.getElementById('toggleGlobalAnswerstemp');
  const iconShow = document.getElementById('icon-showtemp');
  const iconHide = document.getElementById('icon-hidetemp');
  const toggleText = document.getElementById('toggleTexttemp');

  const toggleAllCards = (show) => {
    document.querySelectorAll('.question-cardtemp').forEach(card => {
      const answers = card.querySelector('.answerstemp');
      const btn = card.querySelector('.toggle-answerstemp');
      const span = btn.querySelector('span');
      const icon = btn.querySelector('svg');

       // Get total questions dynamically inside this card
    const questionCount = card.querySelectorAll('.answerstemp > div').length;


      if (show) {
        answers.classList.remove('hidden');
        span.textContent = 'Hide Questions';
        icon.classList.add('rotate-90');
      } else {
        answers.classList.add('hidden');
      span.textContent = `Show Questions (${questionCount})`;
        icon.classList.remove('rotate-90');
      }
    });
  };

  globalToggleBtn.addEventListener('click', () => {
    globalVisible = !globalVisible;

    // Toggle icon and text
    iconShow.classList.toggle('hidden', globalVisible);
    iconHide.classList.toggle('hidden', !globalVisible);
    toggleText.textContent = globalVisible ? 'Hide Questions' : 'Show Questions';

    globalToggleBtn.classList.toggle('border-blue-300', !globalVisible);
    globalToggleBtn.classList.toggle('border-gray-300', globalVisible);
    globalToggleBtn.classList.toggle('text-blue-600', !globalVisible);
    globalToggleBtn.classList.toggle('text-gray-600', globalVisible);

    toggleAllCards(globalVisible);
  });

  // Individual toggle per card
  document.querySelectorAll('.toggle-answerstemp').forEach(button => {
    button.addEventListener('click', () => {
      const card = button.closest('.question-cardtemp');
      const answers = card.querySelector('.answerstemp');
      const icon = button.querySelector('svg');
      const text = button.querySelector('span');

       // Get the total questions for this card
    const questionCount = card.querySelectorAll('.answerstemp > div').length;


      const isVisible = !answers.classList.contains('hidden');
      if (isVisible) {
      answers.classList.add('hidden');
      text.textContent = `Show Questions (${questionCount})`;
      icon.classList.remove('rotate-90');
      } else {
        answers.classList.remove('hidden');
        text.textContent = 'Hide Questions';
        icon.classList.add('rotate-90');
      }
    });
  });
});
</script>

<!-- tempplet toggal  -->

<!-- addd model js  -->
<script>
  document.addEventListener('DOMContentLoaded', () => {


    const modal = document.getElementById('templatesModalWrapper');
    const openBtn = document.getElementById('openTemplatesModal');
    const closeBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelBtn');

      const availableEl = document.getElementById('available');
      const templateEl = document.getElementById('template');
      const templateCount = document.getElementById('templateCount');
      const searchInput = document.getElementById('searchInput');
      const categoryFilter = document.getElementById('categoryFilter');

      // Dynamic data from backend
      const data = [
          @foreach($customerAdminCategory as $category)
              @foreach($category->questions as $question)
                  {
                      id: {{ $question->id }},
                      text: "{{ addslashes($question->question_name) }}",
                      category: "{{ addslashes($category->category_name) }}",
                      required: {{ $question->required_question ? 'true' : 'false' }},
                      question_delivery_text: "{{  $question->question_delivery_text }}",
                      question_return_text: "{{  $question->question_return_text }}"
                  },
              @endforeach
          @endforeach
      ];
      // Sort alphabetically by question name
data.sort((a, b) => a.text.localeCompare(b.text));

    window.templateData = [];

   const openModal = () => {
    const form = document.getElementById('templateForm');
    form.reset();

    form.action = "{{ route('admin.checklist-management.customer-admin.templates.store') }}";

    const methodField = form.querySelector('input[name="_method"]');
    if (methodField) methodField.remove();

    // 🔹 Only reset here for Add
    templateData = [];
    syncQuestionsInput();

    const titleEl = modal.querySelector('h3');
    if (titleEl) titleEl.textContent = "Add New Template";

    renderAvailable();
    renderTemplate();

    modal.style.display = 'flex';
};



    const closeModal = () => {
      modal.style.display = 'none';
    };

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });





window.syncQuestionsInput = function () {
      const questionsInput = document.getElementById('questionsInput');
      questionsInput.value = JSON.stringify(templateData);
  }
    window.renderAvailable = function () {

        const searchText = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value;

        availableEl.innerHTML = '';
        data.forEach(item => {
            if (!templateData.some(t => t.id === item.id)) {
                if ((selectedCategory === 'All' || item.category === selectedCategory) &&
                    item.text.toLowerCase().includes(searchText)) {

                    const div = document.createElement('div');
                    div.className = 'p-3 bg-white border border-gray-300 rounded-md flex items-center justify-between gap-3 cursor-move';
                    div.setAttribute('data-id', item.id);

                    div.innerHTML = `
                        <div class="flex items-center gap-3 w-full">
                            <span class="drag-handle w-6 h-6 flex items-center justify-center text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6h.01M10 10h.01M10 14h.01M14 6h.01M14 10h.01M14 14h.01" />
                                </svg>
                            </span>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">${item.text}  ${item.required ? '<span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full font-medium">Required</span>' : ''}</p> 
                                <p class="pt-2 flex items-center gap-2 text-xs font-regular text-blue-900">
    <svg class="w-4 h-4 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M19.5 17.5C19.5 18.8807 18.3807 20 17 20C15.6193 20 14.5 18.8807 14.5 17.5C14.5 16.1193 15.6193 15 17 15C18.3807 15 19.5 16.1193 19.5 17.5Z" stroke="currentColor" />
        <path d="M9.5 17.5C9.5 18.8807 8.38071 20 7 20C5.61929 20 4.5 18.8807 4.5 17.5C4.5 16.1193 5.61929 15 7 15C8.38071 15 9.5 16.1193 9.5 17.5Z" stroke="currentColor" />
        <path d="M14.5 17.5H9.5M19.5 17.5H20.2632C20.4831 17.5 20.5931 17.5 20.6855 17.4885C21.3669 17.4036 21.9036 16.8669 21.9885 16.1855C22 16.0931 22 15.9831 22 15.7632V13C22 9.41015 19.0899 6.5 15.5 6.5M15 15.5V7C15 5.58579 15 4.87868 14.5607 4.43934C14.1213 4 13.4142 4 12 4H5C3.58579 4 2.87868 4 2.43934 4.43934C2 4.87868 2 5.58579 2 7V15C2 15.9346 2 16.4019 2.20096 16.75C2.33261 16.978 2.52197 17.1674 2.75 17.299C3.09808 17.5 3.56538 17.5 4.5 17.5" stroke="currentColor" />
        <path d="M9.32653 12L10.8131 10.8258C11.6044 10.2008 12 9.88833 12 9.5M9.32653 7L10.8131 8.17417C11.6044 8.79917 12 9.11168 12 9.5M12 9.5L5 9.5" stroke="currentColor" />
    </svg>
    ${item.question_delivery_text}
</p>
                                <p class="flex items-center gap-2 text-xs font-regular text-green-500">
    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" color="currentColor"><path d="M17 20C18.1046 20 19 19.1046 19 18C19 16.8954 18.1046 16 17 16C15.8954 16 15 16.8954 15 18C15 19.1046 15.8954 20 17 20Z" stroke="currentColor"></path><path d="M7 20C8.10457 20 9 19.1046 9 18C9 16.8954 8.10457 16 7 16C5.89543 16 5 16.8954 5 18C5 19.1046 5.89543 20 7 20Z" stroke="currentColor"></path><path d="M19 11H22V13C22 15.357 22 16.5355 21.2678 17.2678C20.7809 17.7546 20.0967 17.9178 19 17.9724M5 17.9724C3.90328 17.9178 3.2191 17.7546 2.73223 17.2678C2 16.5355 2 15.357 2 13V9C2 6.64298 2 5.46447 2.73223 4.73223C3.46447 4 4.64298 4 7 4H10.3C11.4168 4 11.9752 4 12.4271 4.14683C13.3404 4.44358 14.0564 5.15964 14.3532 6.07295C14.5 6.52485 14.5 7.08323 14.5 8.2C14.5 9.42079 14.5 10.0312 14.1657 10.444C14.0998 10.5254 14.0254 10.5998 13.944 10.6657C13.5312 11 12.9208 11 11.7 11H8M15 18H9" stroke="currentColor"></path><path d="M14.5 6H16.3212C17.7766 6 18.5042 6 19.0964 6.35371C19.6886 6.70742 20.0336 7.34811 20.7236 8.6295L22 11" stroke="currentColor"></path><path d="M10 13C10 13 9.3279 12.4436 8.73729 11.9161C8.31975 11.5803 8 11.2926 8 11.0048C8 10.7498 8.24949 10.5128 8.6558 10.1415C9.23188 9.66187 10 9 10 9" stroke="currentColor"></path></svg>
     ${item.question_return_text}
</p>

<p class="text-xs text-gray-500">${item.category}</p>
                            </div>
                            
                        </div>
                    `;
                    availableEl.appendChild(div);
                }
            }
        });
    }


      window.renderTemplate = function () {


        templateEl.innerHTML = '';

        if (templateData.length === 0) {
            templateEl.innerHTML = `
                <div class="text-center text-sm text-gray-500 py-12">
                    <p class="mb-2">Drag questions here to build your template</p>
                </div>
            `;
        } else {
            templateData.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'p-3 bg-blue-50 border border-blue-200 rounded-md space-y-1';
                div.innerHTML = `
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 flex items-center justify-center bg-blue-100 text-blue-600 rounded-full text-xs font-bold">${index + 1}</span>
                            <p class="font-medium text-gray-900 text-sm">${item.text}</p>
                        </div>
                        <button type="button" data-remove="${index}" class="text-red-500 hover:text-red-700 font-bold">✕</button>
                    </div>
                     <div class="flex flex-col gap-2 mt-1 text-xs text-gray-600 pl-8">

                       <p class="pt-2 flex items-center gap-2 text-xs font-regular text-blue-900">
    <svg class="w-4 h-4 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M19.5 17.5C19.5 18.8807 18.3807 20 17 20C15.6193 20 14.5 18.8807 14.5 17.5C14.5 16.1193 15.6193 15 17 15C18.3807 15 19.5 16.1193 19.5 17.5Z" stroke="currentColor" />
        <path d="M9.5 17.5C9.5 18.8807 8.38071 20 7 20C5.61929 20 4.5 18.8807 4.5 17.5C4.5 16.1193 5.61929 15 7 15C8.38071 15 9.5 16.1193 9.5 17.5Z" stroke="currentColor" />
        <path d="M14.5 17.5H9.5M19.5 17.5H20.2632C20.4831 17.5 20.5931 17.5 20.6855 17.4885C21.3669 17.4036 21.9036 16.8669 21.9885 16.1855C22 16.0931 22 15.9831 22 15.7632V13C22 9.41015 19.0899 6.5 15.5 6.5M15 15.5V7C15 5.58579 15 4.87868 14.5607 4.43934C14.1213 4 13.4142 4 12 4H5C3.58579 4 2.87868 4 2.43934 4.43934C2 4.87868 2 5.58579 2 7V15C2 15.9346 2 16.4019 2.20096 16.75C2.33261 16.978 2.52197 17.1674 2.75 17.299C3.09808 17.5 3.56538 17.5 4.5 17.5" stroke="currentColor" />
        <path d="M9.32653 12L10.8131 10.8258C11.6044 10.2008 12 9.88833 12 9.5M9.32653 7L10.8131 8.17417C11.6044 8.79917 12 9.11168 12 9.5M12 9.5L5 9.5" stroke="currentColor" />
    </svg>
    ${item.question_delivery_text}
</p>
                                <p class="flex items-center gap-2 text-xs font-regular text-green-500">
    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" color="currentColor"><path d="M17 20C18.1046 20 19 19.1046 19 18C19 16.8954 18.1046 16 17 16C15.8954 16 15 16.8954 15 18C15 19.1046 15.8954 20 17 20Z" stroke="currentColor"></path><path d="M7 20C8.10457 20 9 19.1046 9 18C9 16.8954 8.10457 16 7 16C5.89543 16 5 16.8954 5 18C5 19.1046 5.89543 20 7 20Z" stroke="currentColor"></path><path d="M19 11H22V13C22 15.357 22 16.5355 21.2678 17.2678C20.7809 17.7546 20.0967 17.9178 19 17.9724M5 17.9724C3.90328 17.9178 3.2191 17.7546 2.73223 17.2678C2 16.5355 2 15.357 2 13V9C2 6.64298 2 5.46447 2.73223 4.73223C3.46447 4 4.64298 4 7 4H10.3C11.4168 4 11.9752 4 12.4271 4.14683C13.3404 4.44358 14.0564 5.15964 14.3532 6.07295C14.5 6.52485 14.5 7.08323 14.5 8.2C14.5 9.42079 14.5 10.0312 14.1657 10.444C14.0998 10.5254 14.0254 10.5998 13.944 10.6657C13.5312 11 12.9208 11 11.7 11H8M15 18H9" stroke="currentColor"></path><path d="M14.5 6H16.3212C17.7766 6 18.5042 6 19.0964 6.35371C19.6886 6.70742 20.0336 7.34811 20.7236 8.6295L22 11" stroke="currentColor"></path><path d="M10 13C10 13 9.3279 12.4436 8.73729 11.9161C8.31975 11.5803 8 11.2926 8 11.0048C8 10.7498 8.24949 10.5128 8.6558 10.1415C9.23188 9.66187 10 9 10 9" stroke="currentColor"></path></svg>
     ${item.question_return_text}
</p>
                      </div>
                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-600 pl-8">
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="h-3 w-3 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-1"
                                   ${item.required ? 'checked' : ''} data-checkbox="${index}">
                            Required
                        </label>
                    </div>
                    <div class="text-xs text-gray-600 pl-8">
                        <button type="button" data-up="${index}" class="text-gray-500 hover:underline">↑ Up</button>
                        <button type="button" data-down="${index}" class="text-gray-500 hover:underline">↓ Down</button>
                    </div>
                `;
                templateEl.appendChild(div);
            });
        }

        templateCount.textContent = templateData.length;
    }

  // Template actions
    templateEl.addEventListener('click', e => {
        const remove = e.target.dataset.remove;
        const up = e.target.dataset.up;
        const down = e.target.dataset.down;

        if (remove !== undefined) {
            templateData.splice(remove, 1);
        } else if (up !== undefined && up > 0) {
            const i = parseInt(up);
            [templateData[i-1], templateData[i]] = [templateData[i], templateData[i-1]];
        } else if (down !== undefined && down < templateData.length-1) {
            const i = parseInt(down);
            [templateData[i], templateData[i+1]] = [templateData[i+1], templateData[i]];
        }
        renderTemplate();
        renderAvailable();
        syncQuestionsInput();
    });

    // Checkbox toggle
    templateEl.addEventListener('change', e => {
        if (e.target.type === 'checkbox') {
            const index = parseInt(e.target.dataset.checkbox);
            templateData[index].required = e.target.checked;
                    syncQuestionsInput();
        }
    });

    // Search & category filter
    searchInput.addEventListener('input', renderAvailable);
    categoryFilter.addEventListener('change', renderAvailable);

    // Drag & drop
    Sortable.create(availableEl, {
        group: { name: 'questions', pull: 'clone', put: false },
        animation: 150
    });

    Sortable.create(templateEl, {
    group: { name: 'questions', pull: false, put: true },
    animation: 150,

    onAdd: evt => {
        const id = parseInt(evt.item.getAttribute('data-id'));
        const item = data.find(q => q.id === id);
        evt.item.remove();

        if (item && !templateData.some(q => q.id === id)) {
            templateData.push({ ...item });
        }

        renderTemplate();
        renderAvailable();
        syncQuestionsInput();
    },

    onUpdate: evt => {

        // Rebuild templateData based on DOM order
        const newOrder = [];
        templateEl.querySelectorAll("[data-remove]").forEach(el => {
            const oldIndex = parseInt(el.getAttribute("data-remove"));
            if (templateData[oldIndex]) {
                newOrder.push(templateData[oldIndex]);
            }
        });

        templateData = newOrder;

        renderTemplate();
        syncQuestionsInput();
    },

        onEnd: evt => {

            syncQuestionsInput();
        }
    });


    renderAvailable();
});
</script>
<!-- addd model js  -->

<!-- edit model js  -->

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('templatesModalWrapper');
    const form = document.getElementById('templateForm');
    const titleEl = modal.querySelector('h3');
    const nameInput = document.getElementById('template_name');
    const descInput = document.getElementById('temp_description');
    const categoryInput = document.querySelector('[name="equipment_category"]');
    const activeCheckbox = document.querySelector('[name="is_active"]');
    const questionsInput = document.getElementById('questionsInput');
    const editButtons = document.querySelectorAll('.edit-template-btn');



    //  Handle Edit
    const openModalForEdit = (btn) => {
        const id = btn.dataset.id;

        // Change form action → update route
        form.action = btn.dataset.route;
        // Add hidden _method for PUT
        if (!form.querySelector('input[name="_method"]')) {
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'PUT';
            form.appendChild(methodField);
        } else {
            form.querySelector('input[name="_method"]').value = 'PUT';
        }

        // Change title
        titleEl.textContent = "Edit Template";

        // Fill inputs
        nameInput.value = btn.dataset.name || "";
        descInput.value = btn.dataset.description || "";


        categoryInput.value = btn.dataset.equipment || "";
        activeCheckbox.checked = btn.dataset.active === "1";

        // Parse questions from dataset

        templateData = JSON.parse(btn.dataset.questions || "[]");

        renderTemplate();
        renderAvailable();
        syncQuestionsInput();

        // Show modal
        modal.style.display = 'flex';
    };

    // Attach to each Edit button
    editButtons.forEach(btn => {
        btn.addEventListener('click', () => openModalForEdit(btn));
    });
});

</script>

<!-- edit model js  -->




<!-- delete- -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-template-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault(); // stop auto submit

            const templateName = form.getAttribute('data-template-name') || 'this template';

            window.showConfirm(
                `Delete "${templateName}"? This action cannot be undone!`,
                'Delete Template'
            ).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>

<!-- delete- -->

<!-- search  -->
 <script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchTemplatesInput");
    const tabButton = document.getElementById("tab-templates");
    const wrapper = document.getElementById("cardsWrapper");
    const cards = document.querySelectorAll(".question-cardtemp");

    if (!searchInput || !wrapper || !tabButton) return;

    function filterCards() {
        const searchText = searchInput.value.toLowerCase();
        wrapper.classList.add("opacity-50", "pointer-events-none");

        setTimeout(() => {
            cards.forEach(card => {
                const title = card.querySelector("h3")?.textContent.toLowerCase() || "";
                const desc = card.querySelector("p")?.textContent.toLowerCase() || "";
                const visible = title.includes(searchText) || desc.includes(searchText);
                card.style.display = visible ? "" : "none";
            });

            wrapper.classList.remove("opacity-50", "pointer-events-none");
        }, 150);
    }

    // 🔹 Check for ?template= in URL
    const params = new URLSearchParams(window.location.search);
    const templateParam = params.get("template");

    if (templateParam) {
        // Pre-fill search & filter
        searchInput.value = templateParam;
        filterCards();

        // 🔹 Auto-activate "Templates" tab — add small delay to ensure tabs are ready
        setTimeout(() => {
            if (typeof showTab === "function") {
                showTab("templates", tabButton);
            }

            // ✅ Visually mark this tab as active (if your showTab doesn’t handle it)
            tabButton.classList.add("bg-blue-100", "text-blue-700", "font-semibold");

            // Optional: focus input for clarity
            searchInput.focus();
        }, 300);
    }

    // 🔹 Filter on input change
    searchInput.addEventListener("input", filterCards);
});
</script>


@if(session('open_edit_template'))
<script>
document.addEventListener("DOMContentLoaded", function () {
    const newId = "{{ session('open_edit_template') }}";

    // Find corresponding edit button
    const btn = document.querySelector(
        `.edit-template-btn[data-route*="${newId}"]`
    );

    if (btn) {
        btn.click(); // automatically open modal
    }
});
</script>
@endif






@endpush

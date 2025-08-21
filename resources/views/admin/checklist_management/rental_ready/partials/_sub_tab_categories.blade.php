    <div id="categoriessub" class="tab-content hidden" data-tab-group="inner">
                    <div class=" p-4 space-y-6">
                        <!-- Header -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800">Categories</h2>
                                <p class="text-sm text-gray-500">Organize questions into logical categories</p>
                            </div>
                            <a href="javascript:void(0)" onclick="openCategoriesModal()"
                                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 mt-3">
                                + New Category
                            </a>
                        </div>
                        <!-- Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($rentalreadycategory as $category)
                                <!-- Category Card -->
                                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">{{ $category->category_name }}</h3>
                                            <p class="text-sm text-gray-600"> {{ $category->questions->count() }} {{ Str::plural('question', $category->questions->count()) }}</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800 edit-category-btn"
                                                    title="Edit"
                                                    data-id="{{ $category->id }}"
                                                    data-name="{{ $category->category_name }}"
                                                    data-description="{{ $category->description }}"
                                                    data-route="{{ route('admin.checklist-management.rental-ready.categories.update', $category->unique_id ) }}">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                                <!-- Delete -->
                                                <form action="{{ route('admin.checklist-management.rental-ready.categories.delete', $category->unique_id) }}"
                                                    method="POST"
                                                    class="inline delete-category-form"
                                                    data-category-name="{{ $category->category_name }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                        <x-heroicon-o-trash class="w-5 h-5" />
                                                    </button>
                                                </form>
                                            
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">{{ $category->description }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
    </div>

<!-- Category Modal -->
<div id="CategoryModalWrapper" class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div
        class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-xl space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full"
        onclick="event.stopPropagation()"
        >
            <div class="flex justify-between items-center px-6 pt-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">New Category</h3>
                <button onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>

             
                {{ html()->form()->attributes([
                    'method' => 'POST',
                    'id' => 'categoryForm',
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'action' => route('admin.checklist-management.rental-ready.categories.store'),
                ])->open() }}

                @csrf
            
            <div class="px-6 grid grid-cols-1 gap-6 bg-gray-50">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category Name *</label>
                    <!-- <input type="text" class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required> -->

                     {{ html()->text('category_name')->attributes([
                        'class' => 'mt-1 w-full px-4 py-2 text-sm border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500',
                        'required' => true,
                        'id' => 'category_name',
                        'data-parsley-required-message' => 'Category name is required.',
                    ])->placeholder('Enter category name') }}
                    @error('category_name')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror


                </div>
                <div class="mb-6" >
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <!-- <textarea class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Optional description..."></textarea> -->
                     {{ html()->textarea('description')->attributes([
                        'class' => 'mt-1 w-full text-sm px-4 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500',
                        'rows' => 3,
                        'id' => 'description',
                    ])->placeholder('Optional description...') }}
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-4">
                <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white dark:bg-gray-700 dark:text-white">Cancel</button>
                 <!-- Submit Button with Loader -->
                <button type="submit" id="submitCategoryBtn" class="relative px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-teal-700 flex items-center justify-center gap-2">
                    <span id="categoryBtnText">Save Category</span>
                    <svg id="categoryBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </button>
            </div>

              {{ html()->form()->close() }}
        </div>
    </div>
</div>


@push('js')

<!-- edit catagray model  -->

<script>
        document.addEventListener("DOMContentLoaded", function () {
        const modalWrapper = document.getElementById("CategoryModalWrapper");
        const form = document.getElementById("categoryForm");
        const categoryNameInput = document.getElementById("category_name");
        const descriptionInput = document.getElementById("description");
        const btnText = document.getElementById("categoryBtnText");

        // Handle Edit button clicks
        document.querySelectorAll(".edit-category-btn").forEach(btn => {
            btn.addEventListener("click", function () {
                const name = this.dataset.name;
                const description = this.dataset.description || "";
                const updateRoute = this.dataset.route;

                // Prefill inputs
                categoryNameInput.value = name;
                descriptionInput.value = description;

                // Switch form to update route
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

                // Change button text
                btnText.textContent = "Update Category";

                modalWrapper.classList.remove("hidden");
            });
        });

        // Reset for Create
        window.openCategoryModal = function () {
            form.setAttribute("action", "{{ route('admin.checklist-management.rental-ready.categories.store') }}");

            let methodField = form.querySelector("input[name='_method']");
            if (methodField) methodField.remove();

            categoryNameInput.value = "";
            descriptionInput.value = "";
            btnText.textContent = "Save Category";

            modalWrapper.classList.remove("hidden");
        };
    });

</script>

<!-- delete-category -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-category-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault(); // stop auto submit

            const categoryName = form.getAttribute('data-category-name') || 'this category';

            window.showConfirm(
                `Delete "${categoryName}"? This action cannot be undone!`,
                'Delete Category'
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

<!-- Open modal -->
<script>
  
  function openCategoriesModal() {
    document.getElementById('CategoryModalWrapper').classList.remove('hidden');
  }

  // Close modal
  function closeCategoryModal() {
    document.getElementById('CategoryModalWrapper').classList.add('hidden');
  }

  // Optional: Close when clicking outside modal content
  window.addEventListener('click', function (e) {
    const modal = document.getElementById('CategoryModalWrapper');
    if (e.target === modal) {
      closeCategoryModal();
    }
  });

  // Optional: Listen to external event to open modal (like Alpine's window event)
  window.addEventListener('open-address-modal', () => {
    openCategoriesModal();
  });
</script>
<!-- Open modal -->

@endpush

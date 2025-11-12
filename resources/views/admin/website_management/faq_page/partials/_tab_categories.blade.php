 <!-- Header -->
 <div class="flex flex-wrap justify-between items-center mb-6">
     <h1 class="text-xl font-semibold text-gray-900">Manage Categories</h1>
     <button onclick="openCategoryModal()" class="bg-blue-600 rounded-lg px-4 py-2 text-sm font-medium text-white">
         + Add Category
     </button>
 </div>

 <!-- Card -->
 <div class="bg-white rounded-lg shadow border border-gray-200">
     <div class="px-6 py-4 border-b border-gray-200">
         <h2 class="text-lg font-semibold text-gray-900">FAQ Categories</h2>
         <p class="text-sm text-gray-500">Drag and drop to reorder categories</p>
     </div>

     <div id="categoryList" class="p-4 sm:p-6 space-y-4 category-block">

         @forelse($categories as $category)
         <!-- Category Card -->
         <div class="category-card category-item flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 p-4 border border-gray-200 rounded-lg bg-white hover:shadow-md transition-shadow cursor-move"
             draggable="true"
             data-id="{{ $category->id }}">

             <div class="flex items-start gap-3">
                 <span class="drag-handle text-gray-400 select-none text-xl leading-4">⋮⋮</span>
                 <div>
                     <h3 class="card-title font-medium text-gray-900">
                         {{ $category->category_name }}
                     </h3>

                     <p class="card-desc text-gray-600 text-sm">
                         {{ $category->description ?? 'No description available.' }}
                     </p>

                     <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-2">
                         {{ $category->faqs_count ?? 0 }} FAQs
                     </span>
                 </div>
             </div>

             <div class="flex items-center gap-3">
                 <!-- Edit Button -->
                 <button type="button" class="edit-btn p-1 text-gray-400 hover:text-blue-600 transition-colors">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-credit-card w-4 h-4">
                         <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                         <line x1="2" x2="22" y1="10" y2="10"></line>
                     </svg>
                 </button>

                 <!-- Delete Button -->
                 <button type="button" class="delete-btn p-1 text-gray-400 hover:text-blue-600 transition-colors">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-trash2 w-4 h-4">
                         <path d="M3 6h18"></path>
                         <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                         <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                         <line x1="10" x2="10" y1="11" y2="17"></line>
                         <line x1="14" x2="14" y1="11" y2="17"></line>
                     </svg>
                 </button>
             </div>
         </div>

         <!-- Edit Form -->
         <div class="edit-form hidden mt-3 bg-white border border-gray-200 rounded-lg p-4 sm:p-6 shadow space-y-5">
             <div>
                 <input class="input-title w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                     type="text"
                     value="{{ $category->category_name }}">
             </div>

             <div>
                 <textarea rows="3"
                     class="input-desc w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                     placeholder="Enter category description...">{{ $category->description }}</textarea>
             </div>

             <label class="flex items-start gap-2 text-sm">
                 <input type="checkbox"
                     class="input-expand mt-1 w-4 h-4 border-gray-300 rounded text-blue-600 focus:ring-blue-500"
                     {{ $category->default_expand ? 'checked' : '' }}>
                 <span class="font-medium text-gray-700">
                     Expand category by default
                     <span class="text-gray-500 font-normal">(shows questions when page loads)</span>
                 </span>
             </label>

             <div>
                 <h3 class="text-sm font-semibold text-gray-900">Category Icon</h3>
                 <p class="text-sm text-gray-500 mb-2">Upload an icon (32x32px recommended, WebP/PNG/JPG, max 1MB)</p>

                 <label class="cursor-pointer inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-upload w-4 h-4 mr-2">
                         <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                         <polyline points="17 8 12 3 7 8"></polyline>
                         <line x1="12" x2="12" y1="3" y2="15"></line>
                     </svg>
                     Choose Icon
                     <input type="file" accept="image/*" class="hidden icon-input block">
                 </label>

                 @if($category->media)
                 <div class="icon-preview-row mt-3 flex items-center gap-3">
                     <img src="{{ $category->media->url }}" alt="Icon" class="icon-preview w-12 h-12 rounded-full object-cover border border-gray-200">
                     <button type="button" class="remove-icon text-red-600 text-sm font-medium hover:underline">Remove Icon</button>
                 </div>
                 @else
                 <div class="icon-preview-row mt-3 flex items-center gap-3 hidden">
                     <img class="icon-preview w-12 h-12 rounded-full object-cover border border-gray-200" alt="Preview">
                     <button type="button" class="remove-icon text-red-600 text-sm font-medium hover:underline">Remove Icon</button>
                 </div>
                 @endif
             </div>

             <div class="flex flex-wrap gap-3">
                 <button type="button" class="save-btn bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg flex items-center gap-1">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-save w-3 h-3 mr-1">
                         <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                         <polyline points="17 21 17 13 7 13 7 21"></polyline>
                         <polyline points="7 3 7 8 15 8"></polyline>
                     </svg>
                     Save
                 </button>

                 <button type="button" class="cancel-btn border border-gray-300 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg flex items-center gap-1">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-x w-3 h-3 mr-1">
                         <path d="M18 6 6 18"></path>
                         <path d="m6 6 12 12"></path>
                     </svg>
                     Cancel
                 </button>
             </div>
         </div>
         @empty
         <p class="text-gray-500 text-center">No categories found.</p>
         @endforelse

     </div>

 </div>



 <div id="NewCategoryModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
     <div class="modal-scrollable w-full mx-auto">
         {{ html()->form('POST')->id('categoryForm')->route('admin.website-management.faq-page.store')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => '',
                ])->acceptsFiles()->open() }}

         @csrf


         <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-xl flex flex-col max-h-full overflow-hidden border border-gray-200">

             <div class="flex items-center justify-between p-4 border-b border-gray-200">
                 <div class="flex items-center space-x-3">
                     <h2 class="text-lg font-semibold text-gray-900">Add New Category</h2>
                 </div>
                 <button type="button" onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
             </div>
             <!-- Scrollable Content -->


             <div class=" overflow-y-auto max-h-[70vh]">
                 <div class="mx-auto bg-white rounded-lg shadow p-6 space-y-5">

                     <!-- Category Name -->
                     <div>
                         <label for="categoryName" class="block text-sm font-semibold text-gray-700 mb-1">Category Name</label>

                         <!-- <input id="categoryName" type="text" placeholder="Enter category name"
                             class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" /> -->
                         {!! html()->text('categoryName')->attributes([
                         'placeholder' => 'Enter category name',
                         'autocomplete' => 'off',

                         ])
                         ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500')
                         ->placeholder('Enter category name')->required() !!}
                     </div>

                     <!-- Description -->
                     <div>
                         <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                         {!! html()->textarea('description')
                         ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500')
                         ->placeholder('Enter category description') !!}
                     </div>

                     <!-- Expand Category -->
                     <div class="flex items-start gap-2">
                         {!! html()->checkbox('default_expand', false)
                         ->id('expandCategory')
                         ->class('mt-1 w-4 h-4 border-gray-300 rounded text-blue-600 focus:ring-blue-500') !!}
                         <label for="expandCategory" class="text-sm text-gray-700">
                             <span class="font-medium">Expand category by default</span>
                             <p class="text-gray-500 text-xs">(shows questions when page loads)</p>
                         </label>
                     </div>

                     <!-- Category Icon -->
                     <div>
                         <h3 class="text-sm font-semibold text-gray-900">Category Icon</h3>
                         <p class="text-xs text-gray-500 mb-2">
                             Upload an icon (32x32px recommended, WebP/PNG/JPG, max 1MB)
                         </p>

                         <label for="iconUpload" class="flex items-center gap-2 w-fit px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 cursor-pointer">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-upload w-4 h-4 mr-2">
                                 <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                 <polyline points="17 8 12 3 7 8"></polyline>
                                 <line x1="12" x2="12" y1="3" y2="15"></line>
                             </svg>
                             Choose Icon
                         </label>
                         <input id="iconUpload" name="category_icon_media" type="file" accept="image/*" class="hidden">

                         <p class="text-xs text-gray-400 mt-1">32x32px, WebP/PNG/JPG</p>

                         <!-- Preview -->
                         <div id="previewContainer" class="hidden mt-3 flex items-center gap-3">
                             <img id="previewImage" src="" alt="Icon Preview" class="w-12 h-12 rounded-full object-cover border border-gray-200">
                             <button type="button" id="removeIcon" class="text-red-600 text-sm font-medium hover:underline">Remove Icon</button>
                         </div>
                     </div>
                 </div>

             </div>
             <div class="flex justify-end gap-2 pt-4 pb-4 px-4 border-t border-gray-200">
                 <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white">Close</button>
                 <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">Add Category</button>
             </div>



         </div>
         {{ html()->form()->close() }}
     </div>
 </div>


 @push('js')

 <script>
     function openCategoryModal() {
         document.getElementById('NewCategoryModal').classList.remove('hidden');
         renderOptions();
     }

     function closeCategoryModal() {
         document.getElementById('NewCategoryModal').classList.add('hidden');
     }
 </script>



 <script>
     const items = document.querySelectorAll('.category-item');
     let dragged = null;

     items.forEach(item => {
         // start
         item.addEventListener('dragstart', e => {
             dragged = item;
             item.classList.add('opacity-50');
             // required for Firefox
             e.dataTransfer.setData('text/plain', '');
             e.dataTransfer.effectAllowed = 'move';
         });

         // end
         item.addEventListener('dragend', () => {
             item.classList.remove('opacity-50');
             dragged = null;
             document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
         });

         // over target
         item.addEventListener('dragover', e => {
             e.preventDefault(); // allow drop
             if (item !== dragged) item.classList.add('drag-over');
         });

         item.addEventListener('dragleave', () => item.classList.remove('drag-over'));

         // drop
         item.addEventListener('drop', e => {
             e.preventDefault();
             item.classList.remove('drag-over');
             if (!dragged || dragged === item) return;

             const parent = item.parentNode;
             const children = Array.from(parent.querySelectorAll('.category-item'));
             const from = children.indexOf(dragged);
             const to = children.indexOf(item);

             if (from < to) {
                 parent.insertBefore(dragged, item.nextSibling);
             } else {
                 parent.insertBefore(dragged, item);
             }
         });
     });
 </script>

 <script>
     document.querySelectorAll('.category-item').forEach(categoryCard => {
         const form = categoryCard.nextElementSibling; // the edit form right after each card
         const editBtn = categoryCard.querySelector('.edit-btn');
         const deleteBtn = categoryCard.querySelector('.delete-btn');
         const cancelBtn = form.querySelector('.cancel-btn');
         const saveBtn = form.querySelector('.save-btn');

         const iconInput = form.querySelector('.icon-input');
         const iconRow = form.querySelector('.icon-preview-row');
         const iconPreview = form.querySelector('.icon-preview');
         const removeIcon = form.querySelector('.remove-icon');

         const titleInput = form.querySelector('.input-title');
         const descInput = form.querySelector('.input-desc');
         const titleText = categoryCard.querySelector('.card-title');
         const descText = categoryCard.querySelector('.card-desc');

         // 📝 Edit Button
         editBtn.addEventListener('click', () => {
             categoryCard.classList.add('hidden');
             form.classList.remove('hidden');
         });

         // ❌ Cancel Button
         cancelBtn.addEventListener('click', () => {
             form.classList.add('hidden');
             categoryCard.classList.remove('hidden');
             iconInput.value = ''; // reset upload
         });

         // 💾 Save Button (local only)
         saveBtn.addEventListener('click', () => {
             titleText.textContent = titleInput.value;
             descText.textContent = descInput.value;

             // handle image preview update
             if (!iconRow.classList.contains('hidden') && iconPreview.src) {
                 let imgInCard = categoryCard.querySelector('img');
                 if (imgInCard) {
                     imgInCard.src = iconPreview.src;
                 } else {
                     const newImg = document.createElement('img');
                     newImg.src = iconPreview.src;
                     newImg.className = 'w-10 h-10 rounded-full object-cover';
                     categoryCard.querySelector('.flex.items-start')
                         .insertBefore(newImg, categoryCard.querySelector('.flex.items-start').children[1]);
                 }
             }

             form.classList.add('hidden');
             categoryCard.classList.remove('hidden');
         });

         //  Image upload preview
         iconInput.addEventListener('change', e => {
             const file = e.target.files[0];
             if (file) {
                 const reader = new FileReader();
                 reader.onload = ev => {
                     iconPreview.src = ev.target.result;
                     iconRow.classList.remove('hidden');
                 };
                 reader.readAsDataURL(file);
             }
         });

         //  Remove image
         removeIcon.addEventListener('click', () => {
             iconInput.value = '';
             iconPreview.src = '';
             iconRow.classList.add('hidden');
         });

        
     });
 </script>


 @endpush
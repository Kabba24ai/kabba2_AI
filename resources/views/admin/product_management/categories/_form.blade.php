{{-- Title & Parent Category --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div>
        <label for="title" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required ">Title</label>
        {{ html()->text('title')
            ->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm  dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('title')
            ])
            ->attributes([
                'maxlength' => 240,
                'data-parsley-maxlength' => 240,
                'placeholder' => 'Title',
                'autocomplete' => 'off'
            ])
            ->required()
        }}
    </div>

    @if(!isset($objProduct) || (isset($objProduct) && $objProduct->childCategories()->count() <= 0))
        <div class="space-y-2">
            <label for="parent_id" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Category</label>
            {{ html()->select('parent_id', $categories, null)
                ->class([
                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                    'border-red-500' => $errors->has('parent_id')
                ])
                ->attributes([
                    'data-control' => 'select2',
                    'autocomplete' => 'off'
                ])
                ->placeholder('Select category')
            }}
        </div>
    @endif

    <div>
        <label for="status" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Status </label>
        {{ html()->select('status', ['Published' => 'Published', 'Draft' => 'Draft', 'Pending' => 'Pending'], isset($objProduct) ? null : 'Active')
            ->class('w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white')
            ->required()
            ->placeholder('Please select')
        }}
    </div>

</div>

{{-- Permalink --}}
<div class="mb-8">
    <label for="slug" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Permalink</label>
    <div class="flex rounded-lg shadow-sm">
        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-100 text-gray-500 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">
            {{ url('/product-category') }}/
        </span>
        {{ html()->text('slug')
            ->class([
                'flex-1 min-w-0 rounded-none rounded-r-lg border border-gray-300 px-4 py-2 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-700',
                'border-red-500' => $errors->has('slug')
            ])
            ->attributes([
                'placeholder' => 'custom-slug-url',
                'autocomplete' => 'off'
            ])
        }}
    </div>
    <small class="text-gray-500 dark:text-gray-400">This will be used in the category URL.</small>
</div>

{{-- Add Products Section --}}
<div class="mb-8">
    <label class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Add Products</label>

    {{-- Search Bar --}}
    <div class="flex items-center space-x-2 mb-4">
        <input type="text" id="product-search" placeholder="Search products..."
            class="w-1/3 md:w-1/4 rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white dark:border-gray-700">
    </div>

    {{-- Selected Products Drag & Drop --}}
    <div id="selected-products" class="grid grid-cols-2 md:grid-cols-4 gap-4 border border-dashed p-4 rounded-lg dark:border-gray-700 min-h-[150px]">
        @if(isset($selectedProducts) && $selectedProducts->count())
            @foreach ($selectedProducts as $product)
                <div class="draggable-item p-2 bg-white dark:bg-gray-800 border rounded shadow cursor-move" data-id="{{ $product->id }}">
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-24 object-cover rounded">
                    <p class="text-sm mt-1 text-center text-gray-700 dark:text-gray-300">{{ $product->name }}</p>
                </div>
            @endforeach
        @endif
    </div>

    <input type="hidden" name="product_order" id="product-order" value="">
</div>


{{-- Short Content --}}
<div class="mb-8">
    <label for="short_content" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Short Content</label>
    {{ html()->textarea('short_content')
        ->class([
            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
            'border-red-500' => $errors->has('short_content')
        ])
        ->attributes([
            'rows' => 3,
            'maxlength' => 1000,
            'data-parsley-maxlength' => 1000,
            'autocomplete' => 'off'
        ])
    }}
</div>

{{-- Content --}}
<div class="mb-6">
    <label for="content" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Description</label>
    {{ html()->textarea('content')
        ->class('ckeditor w-full min-h-[300px] rounded-md border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white border-gray-300 focus:ring-brand-500 focus:border-brand-500')
        ->attributes([
            'autocomplete' => 'off',
            'id' => 'content-editor'
        ])
    }}
</div>

{{-- Image Upload --}}
<div class="mb-8">
    <label class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Image</label>
    @if(isset($category) && $category->image)
        <div class="relative w-40 mb-2">
            <img src="{{ asset('storage/' . $category->image) }}" alt="Selected Image" class="rounded shadow border w-full h-auto">
            <button type="button" onclick="removeImage()" class="absolute top-0 right-0 bg-white rounded-full p-1 shadow -mt-2 -mr-2">
                ✕
            </button>
        </div>
        <input type="hidden" name="remove_image" id="remove_image" value="0">
    @endif

    <input type="file" name="image" accept="image/*" class="block text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose image</p>
</div>

<div class="flex items-center space-x-3 mb-6">
    <label for="is_featured" class="flex items-center cursor-pointer relative">
        {{-- Invisible Checkbox --}}
        {{ html()->checkbox('is_featured', old('is_featured', $category->is_featured ?? false))
            ->class('sr-only peer')
            ->id('is_featured')
            ->value(1) }}

        {{-- Toggle Track --}}
        <div class="w-11 h-6 bg-gray-300 rounded-full peer-checked:bg-blue-600 transition-colors duration-300"></div>

        {{-- Toggle Circle --}}
        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-300 transform peer-checked:translate-x-5 z-10"></div>
    </label>

    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Is featured?</span>
</div>

{{-- SEO Meta Section --}}
<div class="border border-gray-200 rounded-md p-4 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 mb-6">
    <div class="flex justify-between items-center mb-2">
        <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Search Engine Optimize</span>
        <a href="#" onclick="document.getElementById('seo-fields').classList.toggle('hidden'); return false;" class="text-sm text-blue-600 hover:underline">Edit SEO meta</a>
    </div>

    {{-- Preview --}}
    <div class="text-sm text-gray-800 dark:text-white">
        <p class="text-blue-600 font-semibold truncate">{{ old('seo_title', $category->seo_title ?? 'Boom Lift Rentals | Cherry Picker Rentals') }}</p>
        <p class="text-green-700 text-xs truncate">
            {{ url('/product-categories') }}/{{ old('slug', $category->slug ?? 'boom-lifts') }}
        </p>
        <p class="text-gray-700 dark:text-gray-300 mt-1">
            {{ old('seo_description', $category->seo_description ?? 'Rent high-quality boom lifts for construction, maintenance, and tree care. Safe, reliable, and cost-effective solutions tailored to your elevated work needs.') }}
        </p>
    </div>

    {{-- Hidden Edit Fields --}}
    <div id="seo-fields" class="mt-4 space-y-4 hidden">
        {{-- SEO Title --}}
        <div>
            {{ html()->label('SEO Title', 'seo_title')->class('block text-sm font-medium text-gray-700 dark:text-gray-300') }}
            {{ html()->text('seo_title', old('seo_title', $category->seo_title ?? ''))
                ->id('seo_title')
                ->class('w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white') }}
        </div>

        {{-- SEO Description --}}
        <div>
            {{ html()->label('SEO Description', 'seo_description')->class('block text-sm font-medium text-gray-700 dark:text-gray-300') }}
            {{ html()->textarea('seo_description', old('seo_description', $category->seo_description ?? ''))
                ->id('seo_description')
                ->attributes(['rows' => 3])
                ->class('w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white') }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    function removeImage() {
        document.getElementById('remove_image').value = '1';
        event.target.closest('div.relative').remove();
    }
</script>
@endpush



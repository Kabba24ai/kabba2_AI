{{-- Title & Parent Category --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div>
        <label for="title" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Title</label>
        {{ html()->text('title')
            ->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white',
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
        @error('title')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    @if(!isset($objProductCategory) || (isset($objProductCategory) && $objProductCategory->childCategories()->count() <= 0))
        <div class="space-y-2">
            <label for="parent_id" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Category</label>
            {{ html()->select('parent_id', $categories, isset($categoryId) ? $categoryId : null)
                ->class([
                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                    'border-red-500' => $errors->has('parent_id')
                ])
                ->attributes([
                    'autocomplete' => 'off'
                ])
                ->placeholder('Select Category')
            }}
            @error('parent_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div>
        <label for="status" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Status</label>
        {{ html()->select('status', ['Published' => 'Published', 'Draft' => 'Draft', 'Pending' => 'Pending'], isset($objProductCategory) ? null : 'Published')
            ->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('status')
            ])
            ->required()
            ->placeholder('Please Select')
        }}
        @error('status')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Permalink --}}
@if (isset($objProductCategory))
    <div class="mb-8">
        <label class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Permalink</label>
        <div class="flex items-center text-sm">
            @php
                if ($objProductCategory->parentCategory) {
                    $categoryUrl = route('front.categories.sub-category', [
                        'slug' => $objProductCategory->parentCategory->slug,
                        'childCategorySlug' => $objProductCategory->slug
                    ]);
                } else {
                    $categoryUrl = route('front.categories.index', ['slug' => $objProductCategory->slug]);
                }
            @endphp
            <a href="{{ $categoryUrl }}" class="text-blue-600 hover:underline break-all" target="_blank">
                {{ $categoryUrl }}
            </a>
        </div>
        <small class="text-gray-500 dark:text-gray-400">This is the public URL for this category.</small>
    </div>
@endif

{{-- Add Products Section --}}
<div>
    @include('admin.product_management.categories.partials._products')
</div>
{{-- Slug --}}

{{-- Short Content --}}
{{-- <div class="mb-8">
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
    @error('short_content')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div> --}}

{{-- Content --}}
<div class="mb-8">
    <label for="content" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Description</label>
    {{ html()->textarea('content')
        ->class('tinymce w-full min-h-[300px] rounded-md border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white border-gray-300 focus:ring-brand-500 focus:border-brand-500')
        ->attributes([
            'autocomplete' => 'off',
            'id' => 'content-editor'
        ])
    }}
    @error('content')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

{{-- Image Upload --}}
<div class="mb-8">
    <label class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">
        Images <span class="text-xs font-normal text-gray-500">(Optimum Image Size: 450 × 450 pixels)</span>
    </label>

    {{-- Existing image display --}}
    @if(isset($objProductCategory) && $objProductCategory->media)
        <div class="relative w-40 mb-2" id="existing-image-container">
            <img src="{{ $objProductCategory->media->getUrl() }}" alt="Selected Image" class="rounded shadow border w-full h-auto">
        </div>
        <input type="hidden" name="remove_image" id="remove_image" value="0">
    @endif

    {{-- File input --}}
    <input type="file" name="media" id="image-input" accept="image/*"
        class="block text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
        @if(!isset($objProductCategory) || (isset($objProductCategory) && !$objProductCategory->media))
            data-parsley-required="true"
            data-parsley-required-message="Please upload an image."
        @endif
    >

    @error('media')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    {{-- Preview container --}}
    <div id="image-preview" class="mt-2 hidden">
        <img src="" alt="Preview" class="w-40 h-auto rounded border shadow">
    </div>
</div>



{{-- <div class="flex items-center space-x-3 mb-6">
    <label for="is_featured" class="flex items-center cursor-pointer relative">
        {{ html()->checkbox('is_featured', old('is_featured', $objProductCategory->is_featured ?? 'No') === 'Yes')
            ->class('sr-only peer')
            ->id('is_featured')
            ->value('Yes') }}
        <div class="w-11 h-6 bg-gray-300 rounded-full peer-checked:bg-blue-600 transition-colors duration-300"></div>
        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-300 transform peer-checked:translate-x-5 z-10"></div>
    </label>
    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Is featured?</span>
</div> --}}

{{-- SEO Meta Section --}}
<div class="border border-gray-200 rounded-md p-4 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 mb-6">
    <div class="flex justify-between items-center mb-2">
        <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Search Engine Optimize</span>
        <a href="#" onclick="document.getElementById('seo-fields').classList.toggle('hidden'); return false;" class="text-sm text-blue-600 hover:underline">Edit SEO meta</a>
    </div>

    <div class="text-sm text-gray-800 dark:text-white">
        <p class="text-blue-600 font-semibold truncate">{{ old('seo_title', $objProductCategory->seo_title ?? 'Sample Category Title') }}</p>
        @if (isset($objProductCategory))
        <p class="text-green-700 text-xs truncate">
             @php
                if ($objProductCategory->parentCategory) {
                    $categoryUrl = route('front.categories.sub-category', [
                        'slug' => $objProductCategory->parentCategory->slug,
                        'childCategorySlug' => $objProductCategory->slug
                    ]);
                } else {
                    $categoryUrl = route('front.categories.index', ['slug' => $objProductCategory->slug]);
                }
            @endphp
            <a href="{{ $categoryUrl }}" class="text-blue-600 hover:underline break-all" target="_blank">
                {{ $categoryUrl }}
            </a>
        </p>
        @endif
        <p class="text-gray-700 dark:text-gray-300 mt-1">
            {{ old('seo_description',  $objProductCategory->seo_description ?? 'Product Description') }}
        </p>
    </div>

    <div id="seo-fields" class="mt-4 space-y-4 hidden">
        <div>
            {{ html()->label('SEO Title', 'seo_title')->class('block text-sm font-medium text-gray-700 dark:text-gray-300') }}
            {{ html()->text('seo_title', old('seo_title'))
                ->id('seo_title')
                ->class('w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white') }}
            @error('seo_title')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            {{ html()->label('SEO Description', 'seo_description')->class('block text-sm font-medium text-gray-700 dark:text-gray-300') }}
            {{ html()->textarea('seo_description', old('seo_description'))
                ->id('seo_description')
                ->attributes(['rows' => 3])
                ->class('w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white') }}
            @error('seo_description')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

@push('js')
<script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
@vite('resources/admin/js/tinymce.js')
<script>
    function removeImage() {
        document.getElementById('remove_image').value = '1';
        const existingImage = document.getElementById('existing-image-container');
        if (existingImage) {
            existingImage.remove();
        }
    }

    document.getElementById('image-input').addEventListener('change', function (event) {
        const file = event.target.files[0];
        const preview = document.getElementById('image-preview');
        const img = preview.querySelector('img');

        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            preview.classList.add('hidden');
            img.src = '';
        }
    });
</script>
@endpush


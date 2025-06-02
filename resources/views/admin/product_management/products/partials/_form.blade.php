<!-- Basic Info -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Product Name Input -->
    <div>
        <label for="name" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
            Product Name
        </label>

        {!! html()->text('name')->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                'border-gray-300' => !$errors->has('name'),
                'border-red-500' => $errors->has('name'),
            ])->attributes([
                'maxlength' => 240,
                'data-parsley-maxlength' => 240,
                'placeholder' => 'Enter Product Name',
                'autocomplete' => 'off',
                'id' => 'name',
            ])->required() !!}

        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Inline Radio Buttons -->
    <div x-data x-init="$store.productForm.selectedType = '{{ old('type', 'rental') }}'">
        <!-- Product Type -->
        <label class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">
            Product Type
        </label>

        <div class="flex flex-wrap items-center gap-4 mb-6">
            <!-- Rental Configuration -->
            <label
                :class="$store.productForm.selectedType === 'rental' ?
                    'text-gray-700 dark:text-gray-200' :
                    'text-gray-500 dark:text-gray-400'"
                class="relative inline-flex items-center gap-2 text-sm font-medium cursor-pointer select-none">
                <input type="radio" name="type" value="rental" x-model="$store.productForm.selectedType"
                    id="rental" class="sr-only">
                <span
                    :class="$store.productForm.selectedType === 'rental' ?
                        'border-brand-500 bg-brand-500' :
                        'bg-transparent border-gray-300 dark:border-gray-700'"
                    class="flex h-5 w-5 items-center justify-center rounded-full border-[1.25px]">
                    <span :class="$store.productForm.selectedType === 'rental' ? 'block' : 'hidden'"
                        class="w-2 h-2 bg-white rounded-full"></span>
                </span>
                Rental Configuration
            </label>

            <!-- Retail Configuration -->
            <label
                :class="$store.productForm.selectedType === 'retail' ?
                    'text-gray-700 dark:text-gray-200' :
                    'text-gray-500 dark:text-gray-400'"
                class="relative inline-flex items-center gap-2 text-sm font-medium cursor-pointer select-none">
                <input type="radio" name="type" value="retail" x-model="$store.productForm.selectedType"
                    id="retail" class="sr-only">
                <span
                    :class="$store.productForm.selectedType === 'retail' ?
                        'border-brand-500 bg-brand-500' :
                        'bg-transparent border-gray-300 dark:border-gray-700'"
                    class="flex h-5 w-5 items-center justify-center rounded-full border-[1.25px]">
                    <span :class="$store.productForm.selectedType === 'retail' ? 'block' : 'hidden'"
                        class="w-2 h-2 bg-white rounded-full"></span>
                </span>
                Retail Configuration
            </label>
        </div>
    </div>
</div>

{{-- Short Content --}}
<div class="mb-8">
    <label for="short_content" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Short
        Content</label>
    {{ html()->textarea('short_content')->class([
            'ckeditor w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
            'border-red-500' => $errors->has('short_content'),
        ])->attributes([
            'rows' => 3,
            'maxlength' => 1000,
            'data-parsley-maxlength' => 1000,
            'autocomplete' => 'off',
            'placeholder' => 'Enter Short Description Of The Product',
        ]) }}
    @error('short_content')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

{{-- Content --}}
<div class="mb-8">
    <label for="content" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Description</label>
    {{ html()->textarea('content')->class(
            'ckeditor w-full min-h-[300px] rounded-md border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white border-gray-300 focus:ring-brand-500 focus:border-brand-500',
        )->attributes([
            'autocomplete' => 'off',
            'id' => 'content-editor',
            'placeholder' => 'Enter Description Of The Product',
        ]) }}
    @error('content')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>


<div x-data="multiImageUpload()" class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Product Images -->
    <div class="border border-dashed border-gray-300 dark:border-gray-600 rounded-md p-4 bg-white dark:bg-gray-800">
        <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
            Images <span class="text-xs font-normal text-gray-500">(Optimum Image Size: 650 × 650 pixels)</span>
        </label>
        <div class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md h-40 flex flex-col justify-center items-center cursor-pointer hover:border-blue-500"
            @click="$refs.imageInput.click()">
            <x-heroicon-o-photo class="w-6 h-6 text-gray-400" />
            <span class="text-sm text-blue-600 mt-1">Add Images</span>
        </div>
        <input type="file" accept="image/*" multiple class="hidden" x-ref="imageInput"
            @change="handleMainImages($event)">

        <!-- Main Images Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-3" x-show="mainImages.length">
            <template x-for="(image, index) in mainImages" :key="index">
                <div class="relative border rounded-lg overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
                    <img :src="image"
                        class="w-full aspect-[650/650] object-cover rounded-t-lg border-b border-gray-200 dark:border-gray-700" />

                    <button type="button" @click="removeMainImage(index)"
                        class="absolute top-1 right-1 bg-red-600 hover:bg-red-700 rounded-full p-1 text-white shadow-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Mouse Over Image -->
    <div class="border border-dashed border-gray-300 dark:border-gray-600 rounded-md p-4 bg-white dark:bg-gray-800">
        <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
            Mouse Over Image
        </label>
        <div class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md h-40 flex flex-col justify-center items-center cursor-pointer hover:border-blue-500"
            @click="$refs.hoverImageInput.click()">
            <x-heroicon-o-photo class="w-6 h-6 text-gray-400" />
            <span class="text-sm text-blue-600 mt-1">Add Image</span>
        </div>
        <input type="file" accept="image/*" class="hidden" x-ref="hoverImageInput"
            @change="handleHoverImage($event)">

        <!-- Hover Image Preview -->
        <template x-if="hoverImage">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-3">
                <div class="relative border rounded-lg overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
                    <img :src="hoverImage"
                        class="w-full aspect-[650/650] object-cover rounded-t-lg border-b border-gray-200 dark:border-gray-700" />

                    <button type="button" @click="hoverImage = null"
                        class="absolute top-1 right-1 bg-red-600 hover:bg-red-700 rounded-full p-1 text-white shadow-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </template>


    </div>
</div>


{{-- First Row: SKU + Barcode (equal width) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
    {{-- SKU --}}
    <div>
        <label for="sku" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">SKU</label>
        {!! html()->text('sku')->attributes([
                'placeholder' => 'Enter SKU',
                'autocomplete' => 'off',
                'id' => 'sku',
            ])->class(
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 border-gray-300 dark:bg-gray-900 dark:text-white',
            ) !!}
    </div>

    {{-- Barcode --}}
    <div>
        <label for="barcode" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Barcode (ISBN,
            UPC, GTIN, etc.)</label>
        {!! html()->text('barcode')->attributes([
                'placeholder' => 'Enter Barcode',
                'autocomplete' => 'off',
                'id' => 'barcode',
            ])->class(
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 border-gray-300 dark:bg-gray-900 dark:text-white',
            ) !!}
    </div>
</div>

{{-- Second Row: Price, Sale Price, Product Cost --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 ">
    {{-- Price --}}
    <div class="lg:col-span-4">
        <label for="price" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Price</label>
        <div class="relative">
            <span
                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400 text-sm">$</span>
            {!! html()->text('price')->attributes([
                    'placeholder' => '0',
                    'autocomplete' => 'off',
                    'id' => 'price',
                ])->class(
                    'pl-6 w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 border-gray-300 dark:bg-gray-900 dark:text-white',
                ) !!}
        </div>
    </div>

    {{-- Sale Price --}}
    <div class="lg:col-span-4">
        <label for="sale_price" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Sale
            Price</label>
        <div class="relative">
            <span
                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400 text-sm">$</span>
            {!! html()->text('sale_price')->attributes([
                    'placeholder' => '0',
                    'autocomplete' => 'off',
                    'id' => 'sale_price',
                ])->class(
                    'pl-6 w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 border-gray-300 dark:bg-gray-900 dark:text-white',
                ) !!}
        </div>
    </div>

    {{-- Product Cost --}}
    <div class="lg:col-span-4">
        <label for="product_cost" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Product
            Cost</label>
        <div class="relative">
            <span
                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400 text-sm">$</span>
            {!! html()->text('product_cost')->attributes([
                    'placeholder' => '0',
                    'autocomplete' => 'off',
                    'id' => 'product_cost',
                ])->class(
                    'pl-6 w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 border-gray-300 dark:bg-gray-900 dark:text-white',
                ) !!}
        </div>
    </div>
</div>

<div x-data x-show="$store.productForm.selectedType === 'rental'" x-cloak>
    @include('admin.product_management.products.partials._rental_delivery_settings')
</div>

<!-- Product Options Placeholder -->
@includeIf('admin.product_management.products.partials._options', ['options' => $options ?? []])

<div x-data="relatedProducts()">
    @include('admin.product_management.products.partials._related_products', [
        'relatedProducts' => $relatedProducts ?? [],
    ])
</div>

<div x-data="{ type: '{{ old('type', 'rental') }}', showTermSelect: false }" x-init="$watch('type', value => { if (value !== 'rental') showTermSelect = false })">
    @include('admin.product_management.products.partials._terms_checklist')
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Categories Column -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4">
        <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">Categories</h4>
        <div class="max-h-64 overflow-y-auto pr-1 space-y-3">
            @foreach ($categories as $category)
                @php
                    $checkboxId = "category_{$category->id}";
                    $isChecked = in_array($category->id, old('categories', []));
                @endphp

                <div x-data="{ checked: {{ $isChecked ? 'true' : 'false' }} }">
                    <label for="{{ $checkboxId }}"
                        class="flex items-center text-sm font-medium text-gray-700 cursor-pointer select-none dark:text-gray-400">
                        <div class="relative">
                            {!! html()->checkbox('categories[]', $isChecked)->value($category->id)->id($checkboxId)->class('sr-only')->attribute('@change', 'checked = !checked') !!}

                            <div :class="checked ? 'border-blue-500 bg-blue-500' :
                                'bg-transparent border-gray-300 dark:border-gray-700'"
                                class="mr-2 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px] transition-colors duration-150">
                                <span :class="checked ? '' : 'opacity-0'">
                                    <x-heroicon-o-check class="w-3.5 h-3.5 text-white" />
                                </span>
                            </div>
                        </div>
                        {{ $category->title }}
                    </label>
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Products must be in at least one category to appear on the website
        </p>
    </div>

    <!-- Sales Funnels Column -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4">
        <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">Sales Funnels</h4>
        <div class="space-y-3">
            @foreach ($funnels as $funnel)
                <div class="flex items-center space-x-2 text-sm">
                    {!! html()->checkbox('funnels[]', in_array($funnel->id, old('funnels', [])))->value($funnel->id)->id("funnel_{$funnel->id}") !!}
                    <label for="funnel_{{ $funnel->id }}" class="text-gray-700 dark:text-gray-300">
                        {{ $funnel->name }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</div>


{{-- SEO Meta Section --}}
<div class="border border-gray-200 rounded-md p-4 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 mb-6">
    <div class="flex justify-between items-center mb-2">
        <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Search Engine Optimize</span>
        <a href="#" onclick="document.getElementById('seo-fields').classList.toggle('hidden'); return false;"
            class="text-sm text-blue-600 hover:underline">Edit SEO meta</a>
    </div>

    <div class="text-sm text-gray-800 dark:text-white">
        <p class="text-blue-600 font-semibold truncate">
            {{ old('seo_title', $objProduct->seo_title ?? 'Sample Category Title') }}</p>
        <p class="text-green-700 text-xs truncate">
            {{ url('/product-categories') }}/{{ old('slug', $objProduct->slug ?? 'Sample Category Slug') }}
        </p>
        <p class="text-gray-700 dark:text-gray-300 mt-1">
            {{ old('seo_description', $objProduct->seo_description ?? 'Product Description') }}
        </p>
    </div>

    <div id="seo-fields" class="mt-4 space-y-4 ">
        <div>
            {{ html()->label('SEO Title', 'seo_title')->class('block text-sm font-medium text-gray-700 dark:text-gray-300') }}
            {{ html()->text('seo_title', old('seo_title'))->id('seo_title')->class(
                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                ) }}
            @error('seo_title')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            {{ html()->label('SEO Description', 'seo_description')->class('block text-sm font-medium text-gray-700 dark:text-gray-300') }}
            {{ html()->textarea('seo_description', old('seo_description'))->id('seo_description')->attributes(['rows' => 3])->class(
                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                ) }}
            @error('seo_description')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>


@push('js')
    <script>
        function multiImageUpload() {
            return {
                mainImages: [],
                hoverImage: null,

                handleMainImages(event) {
                    this.mainImages = [];
                    const files = event.target.files;
                    if (!files) return;

                    for (let i = 0; i < files.length; i++) {
                        const reader = new FileReader();
                        reader.onload = e => this.mainImages.push(e.target.result);
                        reader.readAsDataURL(files[i]);
                    }
                },

                removeMainImage(index) {
                    this.mainImages.splice(index, 1);
                },

                handleHoverImage(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = e => this.hoverImage = e.target.result;
                    reader.readAsDataURL(file);
                }
            }
        }

        document.addEventListener('alpine:init', () => {
            // Alpine store for shared state
            Alpine.store('productForm', {
                selectedType: '{{ old('type', 'rental') }}'
            });

            // Watch and reset rental inputs when switching away
            Alpine.effect(() => {
                if (Alpine.store('productForm').selectedType !== 'rental') {
                    const rentalSection = document.querySelector(
                        '[x-show="$store.productForm.selectedType === \'rental\'"]');

                    if (rentalSection) {
                        rentalSection.querySelectorAll('input, select, textarea').forEach(el => {
                            if (['checkbox', 'radio'].includes(el.type)) {
                                el.checked = false;
                            } else {
                                el.value = '';
                            }
                        });
                    }
                }
            });
        });
    </script>
@endpush

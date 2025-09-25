<!-- Basic Info -->
<div class="grid grid-cols-1 lg:grid-cols-7 grid-flow-row-dense gap-6">
    <div class="col-span-1">
        <label for="product_name" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
            Product Name
        </label>

        {!! html()->text('product_name')->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                'border-gray-300' => !$errors->has('product_name'),
                'border-red-500' => $errors->has('product_name'),
            ])->attributes([
                'maxlength' => 240,
                'data-parsley-maxlength' => 240,
                'placeholder' => 'Enter Product Name',
                'autocomplete' => 'off',
                'id' => 'product_name',
                // <-- tell Parsley where to put its error
                'data-parsley-errors-container' => '#product_name_error',
            ])->required() !!}

        {{-- One place for both client (Parsley) and server errors --}}
        <div id="product_name_error" class="mt-1">
            @error('product_name')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>


    <!-- Product Type -->
    <div class="col-span-2" x-data x-init="$nextTick(() => $store.productForm.selectedType = '{{ old('product_type', $objProduct->product_type ?? 'Rental') }}')">
        <label class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Product Type</label>
        <div class="flex flex-wrap items-center gap-4 mb-2">
            <!-- Rental Option -->
            <label
                :class="$store.productForm.selectedType === 'Rental' ? 'text-gray-700 dark:text-gray-200' :
                    'text-gray-500 dark:text-gray-400'"
                class="relative inline-flex items-center gap-2 text-sm font-medium cursor-pointer select-none">
                <input type="radio" name="product_type" value="Rental" x-model="$store.productForm.selectedType"
                    class="sr-only">
                <span
                    :class="$store.productForm.selectedType === 'Rental' ? 'border-brand-500 bg-brand-500' :
                        'bg-transparent border-gray-300 dark:border-gray-700'"
                    class="flex h-5 w-5 items-center justify-center rounded-full border-[1.25px]">
                    <span :class="$store.productForm.selectedType === 'Rental' ? 'block' : 'hidden'"
                        class="w-2 h-2 bg-white rounded-full"></span>
                </span>
                Rental Configuration
            </label>
            <!-- Retail Option -->
            <label
                :class="$store.productForm.selectedType === 'Retail' ? 'text-gray-700 dark:text-gray-200' :
                    'text-gray-500 dark:text-gray-400'"
                class="relative inline-flex items-center gap-2 text-sm font-medium cursor-pointer select-none">
                <input type="radio" name="product_type" value="Retail" x-model="$store.productForm.selectedType"
                    class="sr-only">
                <span
                    :class="$store.productForm.selectedType === 'Retail' ? 'border-brand-500 bg-brand-500' :
                        'bg-transparent border-gray-300 dark:border-gray-700'"
                    class="flex h-5 w-5 items-center justify-center rounded-full border-[1.25px]">
                    <span :class="$store.productForm.selectedType === 'Retail' ? 'block' : 'hidden'"
                        class="w-2 h-2 bg-white rounded-full"></span>
                </span>
                Retail Configuration
            </label>
        </div>
        @error('product_type')
            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    @php
        $seoSlug = isset($objProduct) ? $objProduct->slug ?? '' : '';
        $seoType = old(
            'product_type',
            isset($objProduct) && $objProduct->product_type == 'Rental' ? 'daily' : 'retail',
        );
        $seoUrl = $seoSlug ? route('front.products.details', ['slug' => $seoSlug, 'productVariant' => $seoType]) : '#';
    @endphp
    <!-- Slug Input -->
    <div class="col-span-3 overflow-hidden">
        <label for="slug" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
            Permalink
        </label>

        {{-- View Mode --}}
        <div id="permalinkView" class="flex items-center">
            <span class="text-gray-500 dark:text-gray-400 text-sm break-words">
                <a href="{{ $seoUrl }}" target="_blank" class="underline text-blue-600 permalink break-all">
                    {{ $seoUrl !== '#' ? $seoUrl : 'Product link will be generated after saving.' }}
                </a>
            </span>
            <button type="button" id="editPermalink"
                class="ml-2 px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300 {{ $seoUrl === '#' ? 'hidden' : '' }}">Edit</button>
            <button type="button" id="copyPermalink"
                class="ml-2 px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300 {{ $seoUrl === '#' ? 'hidden' : '' }}">Copy</button>
        </div>

        {{-- Edit Mode --}}
        <div id="permalinkEdit" class="hidden">
            <div class="flex items-center">
                <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $seoUrl }}/</span>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $objProduct->slug ?? '') }}"
                    class="flex-1 ml-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <button type="button" id="donePermalink"
                    class="ml-2 px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300">Done</button>
            </div>
        </div>
    </div>

    <!-- Status Input -->
    <div class="col-span-1">
        <label for="Status" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
            Status
        </label>
        {!! html()->select(
                'status',
                [
                    '' => 'Select Status',
                    'Draft' => 'Draft',
                    'Pending' => 'Pending',
                    'Published' => 'Published',
                ],
                old('status', $objProduct->status ?? 'Published'),
            )->id('status')->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('status'),
            ])->required() !!}
        @error('status')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>


{{-- Short Description --}}
<div class="mb-8">
    <label for="short_description"
        class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Short
        Description
    </label>
    {{ html()->textarea('short_description')->class([
            'tinymce w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
            'border-red-500' => $errors->has('short_description'),
        ])->attributes([
            'rows' => 3,
            'maxlength' => 2000,
            'placeholder' => 'Enter Short Description Of The Product',
            'autocomplete' => 'off',
            'data-parsley-errors-container' => '#short-description-error',
        ])->required() }}
    <p id="short-description-error" class="mt-1 text-sm text-red-600 dark:text-red-400"></p>
    @error('short_description')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

{{-- Description --}}
<div class="mb-8">
    <label for="description"
        class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Description</label>
    {{ html()->textarea('description')->class([
            'tinymce w-full min-h-[300px] rounded-md border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
            'border-red-500' => $errors->has('description'),
        ])->attributes([
            'id' => 'description',
            'placeholder' => 'Enter Description Of The Product',
            'autocomplete' => 'off',
            'data-parsley-errors-container' => '#description-error',
        ])->required() }}
    <p id="description-error" class="mt-1 text-sm text-red-600 dark:text-red-400"></p>
    @error('description')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

@php
    $mediaChildren = isset($objProduct) ? $objProduct->mediaChildren ?? [] : [];
    $hoverImage = isset($objProduct) ? optional($objProduct->media)->getUrl() : null;
    $hoverImageId = isset($objProduct) ? optional($objProduct->media)->id : null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">

    <!-- Main Images Upload -->
    <div
        class="md:col-span-3 border border-dashed border-gray-300 dark:border-gray-600 rounded-md p-4 bg-white dark:bg-gray-800">
        <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
            Images <span class="text-xs font-normal text-gray-500">(Optimum Image Size: 650 × 650 pixels)</span>
        </label>
        <div class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md h-40 flex flex-col justify-center items-center cursor-pointer hover:border-blue-500"
            onclick="document.getElementById('mainImageInput').click()">
            <x-heroicon-o-photo class="w-6 h-6 text-gray-400" />
            <span class="text-sm text-blue-600 mt-1">Add Images</span>
        </div>
        <input type="file" name="images[]" id="mainImageInput" accept="image/*" multiple class="hidden">

        @error('images')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('images.*')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <!-- Main Image Previews -->
        <div class="grid grid-cols-4 gap-2 mt-4" id="mainImagePreview">
            @foreach ($mediaChildren as $image)
                <div class="relative group" data-key="{{ $image->id }}">
                    <a href="{{ $image->media->getUrl() }}" target="_blank">
                        <img src="{{ $image->media->getUrl() }}" class="w-full h-60 object-cover rounded-md border" />
                    </a>

                    <button type="button"
                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-80 hover:opacity-100"
                        onclick="this.closest('.group').remove();">
                        ×
                    </button>
                    <input type="hidden" name="existing_images[]" value="{{ $image->id }}">
                </div>
            @endforeach
            <input type="hidden" name="image_sort_order" id="image_sort_order">
        </div>
    </div>

    <!-- Hover Image Upload -->
    <div
        class="md:col-span-1 border border-dashed border-gray-300 dark:border-gray-600 rounded-md p-4 bg-white dark:bg-gray-800">
        <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
            Mouse Over Image
        </label>
        <div class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md h-40 flex flex-col justify-center items-center cursor-pointer hover:border-blue-500"
            onclick="document.getElementById('hoverImageInput').click()">
            <x-heroicon-o-photo class="w-6 h-6 text-gray-400" />
            <span class="text-sm text-blue-600 mt-1">Add Image</span>
        </div>
        <input type="file" name="hover_image" id="hoverImageInput" accept="image/*" class="hidden">

        @error('hover_image')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <!-- Hover Image Preview -->
        @if (!empty($hoverImage))
            <div class="relative mt-4" id="hoverImagePreview">
                <img src="{{ $hoverImage }}" alt="Hover Image" class="w-full h-60 object-cover rounded-md border"
                    id="hoverImageTag" />
                <button type="button"
                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-80 hover:opacity-100"
                    onclick="removeHoverImage()">
                    ×
                </button>
                <input type="hidden" name="existing_hover_image" value="{{ $hoverImageId }}">
            </div>
        @else
            <div class="relative mt-4" id="hoverImagePreview" style="display: none;">
                <img id="hoverImageTag" class="w-full h-60 object-cover rounded-md border" />
                <button type="button"
                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-80 hover:opacity-100"
                    onclick="removeHoverImage()">
                    ×
                </button>
            </div>
        @endif
    </div>
</div>



{{-- Retail Fields --}}
<div x-data x-show="$store.productForm.selectedType === 'Retail'" x-cloak
    class="grid grid-cols-1 sm:grid-cols-5 lg:grid-cols-7 gap-4 mt-6">
    {{-- Price --}}
    <div class="md:col-span-1">
        <label for="retail_price"
            class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Price</label>
        <div class="relative">
            <span
                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
            {!! html()->text('retail_price')->attributes([
                    'placeholder' => '0',
                    'autocomplete' => 'off',
                    'id' => 'retail_price',
                    'data-parsley-maxlength' => 8,
                    'data-parsley-errors-container' => '#retail-price-errors',
                    'maxlength' => 8,
                ])->class([
                    'pl-6 w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    'border-red-500' => $errors->has('retail_price'),
                    'border-gray-300' => !$errors->has('retail_price'),
                ]) !!}
        </div>
        <div id="retail-price-errors"></div>
        @error('retail_price')
            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Sale Price --}}
    <div class="md:col-span-1">
        <label for="retail_sale_price" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Sale
            Price</label>
        <div class="relative">
            <span
                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
            {!! html()->text('retail_sale_price')->attributes([
                    'placeholder' => '0',
                    'autocomplete' => 'off',
                    'id' => 'retail_sale_price',
                    'data-parsley-maxlength' => 8,
                    'maxlength' => 8,
                    'data-parsley-errors-container' => '#retail-sale-price-errors',
                ])->class([
                    'pl-6 w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    'border-red-500' => $errors->has('retail_sale_price'),
                    'border-gray-300' => !$errors->has('retail_sale_price'),
                ]) !!}
        </div>
        <div id="retail-sale-price-errors"></div>
        @error('retail_sale_price')
            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Product Cost --}}
    <div class="md:col-span-1">
        <label for="retail_product_cost"
            class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Product
            Cost</label>
        <div class="relative">
            <span
                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
            {!! html()->text('retail_product_cost')->attributes([
                    'placeholder' => '0',
                    'autocomplete' => 'off',
                    'id' => 'retail_product_cost',
                    'data-parsley-maxlength' => 8,
                    'maxlength' => 8,
                    'data-parsley-errors-container' => '#retail-product-cost-errors',
                ])->class([
                    'pl-6 w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    'border-red-500' => $errors->has('retail_product_cost'),
                    'border-gray-300' => !$errors->has('retail_product_cost'),
                ]) !!}
        </div>
        <div id="retail-product-cost-errors"></div>
        @error('retail_product_cost')
            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- SKU --}}
    <div class="md:col-span-2">
        <label for="sku" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">SKU</label>
        {!! html()->text('sku')->attributes([
                'placeholder' => 'Enter SKU',
                'autocomplete' => 'off',
                'id' => 'sku',
            ])->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('sku'),
                'border-gray-300' => !$errors->has('sku'),
            ]) !!}
        @error('sku')
            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Barcode --}}
    <div class="md:col-span-2">
        <label for="barcode" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Barcode</label>
        {!! html()->text('barcode')->attributes([
                'placeholder' => 'Enter Barcode',
                'autocomplete' => 'off',
                'id' => 'barcode',
            ])->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('barcode'),
                'border-gray-300' => !$errors->has('barcode'),
            ]) !!}
        @error('barcode')
            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

<div x-data x-show="$store.productForm.selectedType === 'Rental'" x-cloak>
    @include('admin.product_management.products.partials._rental_delivery_settings')
</div>

<!-- Product Options Placeholder -->
@include('admin.product_management.products.partials._options', ['options' => $productOptions ?? []])

<div>
    @include('admin.product_management.products.partials._related_products', [
        'relatedProducts' => $relatedProducts ?? [],
    ])
</div>


<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    @include('admin.product_management.products.partials._terms_checklist')

    <!-- Categories Column -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-6">
        <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-2">Add 1 or More Categories</h4>

        @if ($categoryTree->isNotEmpty())
            <div class="max-h-64 overflow-y-auto pr-2 custom-scroll">
                <ul class="space-y-2">
                    @foreach ($categoryTree as $category)
                        @include('admin.product_management.products.partials._category-node', [
                            'category' => $category,
                        ])
                    @endforeach
                </ul>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">No categories available.</p>
        @endif
    </div>

    <!-- Sales Funnels Column -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4">
        <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-2">Add 1 or More Sales Funnels</h4>

        <div class="space-y-3 max-h-64 overflow-y-auto pr-2 custom-scroll">
            <div class="flex items-center space-x-2 text-sm" x-data x-show="$store.productForm.selectedType === 'Rental'" x-cloak>
                {!! html()->checkbox('is_default_funnel') !!}
                <label for="is_default_funnel" class="text-gray-700 dark:text-gray-300">
                    Default Sales Funnel
                </label>
            </div>
            @if ($funnels->isNotEmpty())
                @foreach ($funnels as $funnel)
                    <div class="flex items-center space-x-2 text-sm">
                        {!! html()->checkbox('funnels[]', in_array($funnel->id, old('funnels', [])))->value($funnel->id)->id("funnel_{$funnel->id}") !!}
                        <label for="funnel_{{ $funnel->id }}" class="text-gray-700 dark:text-gray-300">
                            {{ $funnel->name }}
                        </label>
                    </div>
                @endforeach
            @else
                {{-- <p class="text-sm text-gray-500 dark:text-gray-400">No  custom sales funnels available.</p> --}}
            @endif
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
            <a href="{{ $seoUrl }}" target="_blank" rel="noopener" class="permalink">
                {{ $seoUrl !== '#' ? $seoUrl : 'Product link will be generated after saving.' }}
            </a>
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
                )->placeholder('SEO Title') }}
            @error('seo_title')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            {{ html()->label('SEO Description', 'seo_description')->class('block text-sm font-medium text-gray-700 dark:text-gray-300') }}
            {{ html()->textarea('seo_description', old('seo_description'))->id('seo_description')->attributes(['rows' => 3])->class(
                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                )->placeholder('SEO Description') }}
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

        document.addEventListener('alpine:init', () => {
            // Alpine store for shared state
            Alpine.store('productForm', {
                selectedType: '{{ old('product_type', $objProduct->product_type ?? 'Rental') }}'
            });

            // Watch and reset Rental inputs when switching away
            Alpine.effect(() => {
                const isRental = Alpine.store('productForm').selectedType === 'Rental';
                // Your section
                const rentalSection = document.querySelector(
                    '[x-show="$store.productForm.selectedType === \'Rental\'"]');

                if (!isRental && rentalSection) {
                    rentalSection.querySelectorAll('input, select, textarea').forEach(el => {
                        if (['checkbox', 'radio'].includes(el.type)) {
                            // el.checked = false;
                        } else {
                            el.value = '';
                        }
                    });
                }
            });

            // Make retail_price required when type is Retail
            Alpine.effect(() => {
                const isRetail = Alpine.store('productForm').selectedType === 'Retail';
                const retailPrice = document.getElementById('retail_price');
                const rentalDaily = document.getElementById('rental_daily');
                const rentalWeekend = document.getElementById('rental_weekend');
                const rentalWeekly = document.getElementById('rental_weekly');
                const rentalMonthly = document.getElementById('rental_monthly');
                const isDefaultFunnel = document.getElementById('is_default_funnel');
                // Delivery fee inputs
                // const standardDelivery = document.querySelector('input[name="standard_delivery_fee"]');
                // const extendedDelivery = document.querySelector('input[name="extended_delivery_fee"]');

                if (isRetail) {
                    retailPrice.setAttribute('required', 'required');
                    rentalDaily.removeAttribute('required');
                    rentalWeekend.removeAttribute('required');
                    rentalWeekly.removeAttribute('required');
                    rentalMonthly.removeAttribute('required');
                } else {
                    rentalDaily.setAttribute('required', 'required');
                    rentalWeekend.setAttribute('required', 'required');
                    rentalWeekly.setAttribute('required', 'required');
                    rentalMonthly.setAttribute('required', 'required');
                    retailPrice.removeAttribute('required');
                    isDefaultFunnel.checked = false;
                }
            });

        });

        // Make refreshImageSortOrder global
        window.refreshImageSortOrder = function() {
            console.log("Refreshing image sort order...");
            const grid = document.getElementById('mainImagePreview');
            const sortInput = document.getElementById('image_sort_order');
            if (!grid || !sortInput) return;

            const keys = Array.from(grid.querySelectorAll('.group'))
                .map(w => w.dataset.key) // e.g. ["existing-12","new-abc","existing-34"]
                .filter(Boolean);

            sortInput.value = JSON.stringify(keys);
        };

        // Initial compute
        window.refreshImageSortOrder();
        document.addEventListener("DOMContentLoaded", function() {
            // Init Sortable
            const grid = document.getElementById('mainImagePreview');
            if (grid) {
                new Sortable(grid, {
                    animation: 150,
                    onEnd: window.refreshImageSortOrder
                });
            }

            const permalinkView = document.getElementById("permalinkView");
            const permalinkEdit = document.getElementById("permalinkEdit");
            // select ALL anchors with the class "permalink"
            const permalinkLinks = document.querySelectorAll(".permalink");
            const editBtn = document.getElementById("editPermalink");
            const doneBtn = document.getElementById("donePermalink");
            const copyBtn = document.getElementById("copyPermalink");
            const slugInput = document.getElementById("slug");

            // Keep an immutable template for the route
            const URL_TEMPLATE =
                "{{ route('front.products.details', ['slug' => ':slug', 'productVariant' => ':variant']) }}";

            // Build URL from current slug + product type
            function computePermalink(slug, selectedType) {
                const cleanedSlug = (slug || "").trim();
                const variant = selectedType === "Rental" ? "daily" : "retail";
                if (!cleanedSlug) {
                    return "Product link will be generated after saving.";
                }
                return URL_TEMPLATE
                    .replace(":slug", encodeURIComponent(cleanedSlug))
                    .replace(":variant", variant);
            }

            // Read latest values and update ALL permalink links
            function updatePermalink() {
                const selectedType = Alpine.store("productForm").selectedType; // always fresh
                const slug = slugInput ? slugInput.value : "";
                const url = computePermalink(slug, selectedType);

                // update every .permalink anchor
                permalinkLinks.forEach((a) => {
                    a.href = url;
                    a.textContent = url;
                });
            }

            // ----- UI wiring -----
            if (editBtn) {
                editBtn.addEventListener("click", function() {
                    permalinkView.classList.add("hidden");
                    permalinkEdit.classList.remove("hidden");
                });
            }

            if (doneBtn) {
                doneBtn.addEventListener("click", function() {
                    updatePermalink(); // recompute with latest values
                    permalinkEdit.classList.add("hidden");
                    permalinkView.classList.remove("hidden");
                });
            }

            if (copyBtn) {
                copyBtn.addEventListener("click", function() {
                    // copy the first permalink’s URL (adjust if you want a different one)
                    const first = permalinkLinks[0];
                    const url = first?.href || "";
                    if (!url) return;
                    navigator.clipboard.writeText(url).then(() => {
                        notyf?.success?.("Permalink copied to clipboard!");
                    });
                });
            }

            // Recompute when the slug is edited
            if (slugInput) {
                slugInput.addEventListener("input", updatePermalink);
                slugInput.addEventListener("change", updatePermalink);
            }

            // Recompute when product type changes (listen to the radios)
            document.querySelectorAll('input[name="product_type"]').forEach((el) => {
                el.addEventListener("change", updatePermalink);
            });

            // Initial render
            //updatePermalink();

            const productNameInput = document.getElementById("product_name");
            const shortDescInput = document.getElementById("short_description");
            const seoTitleInput = document.getElementById("seo_title");
            const seoDescInput = document.getElementById("seo_description");

            // Sync Product Name → SEO Title (only if SEO Title is blank)
            if (productNameInput && seoTitleInput) {
                productNameInput.addEventListener('input', () => {
                    seoTitleInput.value = productNameInput.value;
                });
            }

            // Sync Short Description → SEO Description (only if SEO Description is blank)
            if (shortDescInput && seoDescInput) {
                shortDescInput.addEventListener('input', () => {
                    seoDescInput.value = shortDescInput.value;
                });
            }

            const mainImageInput = document.getElementById("mainImageInput");
            const mainImagePreview = document.getElementById("mainImagePreview");

            const hoverImageInput = document.getElementById("hoverImageInput");
            const hoverImagePreview = document.getElementById("hoverImagePreview");
            const hoverImageTag = document.getElementById("hoverImageTag");

            // Helper: Create FileList from a single File
            function createFileList(files) {
                const dataTransfer = new DataTransfer();
                files.forEach(file => dataTransfer.items.add(file));
                return dataTransfer.files;
            }

            // Append new images to main preview
            mainImageInput.addEventListener("change", function() {
                const files = Array.from(this.files);
                // sanitize: lowercase, replace spaces with dashes, remove non-word chars
                files.forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const wrapper = document.createElement("div");
                        wrapper.className = "relative group";
                        wrapper.dataset.key = `new-${file.name}`;

                        const anchor = document.createElement("a");
                        anchor.href = e.target.result;
                        anchor.target = "_blank";

                        const img = document.createElement("img");
                        img.src = e.target.result;
                        img.className = "w-full h-60 object-cover rounded-md border";

                        const button = document.createElement("button");
                        button.type = "button";
                        button.className =
                            "absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-80 hover:opacity-100";
                        button.innerHTML = "&times;";
                        button.onclick = () => wrapper.remove();

                        const hiddenInput = document.createElement("input");
                        hiddenInput.type = "file";
                        hiddenInput.name = "images[]";
                        hiddenInput.files = createFileList([file]);
                        hiddenInput.className = "hidden";

                        anchor.appendChild(img);
                        wrapper.appendChild(anchor);
                        wrapper.appendChild(button);
                        wrapper.appendChild(hiddenInput);
                        mainImagePreview.appendChild(wrapper);
                    };
                    reader.readAsDataURL(file);
                });

                this.value = '';
            });

            // Hover image preview
            hoverImageInput.addEventListener("change", function() {
                const file = this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    hoverImageTag.src = e.target.result;
                    hoverImagePreview.style.display = "block";

                    // Remove any existing input to avoid duplication
                    const oldHiddenInput = document.querySelector('input[name="existing_hover_image"]');
                    if (oldHiddenInput) oldHiddenInput.remove();
                };
                reader.readAsDataURL(file);
            });

            // Expose to global for use in HTML
            window.removeHoverImage = function() {
                if (hoverImageTag) hoverImageTag.src = "";
                if (hoverImagePreview) hoverImagePreview.style.display = "none";
                if (hoverImageInput) hoverImageInput.value = null;

                // Remove hidden input if it exists
                const existingHoverInput = document.querySelector('input[name="existing_hover_image"]');
                if (existingHoverInput) existingHoverInput.remove();
            };
        });
    </script>
@endpush

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
                'placeholder' => 'Enter product name',
                'autocomplete' => 'off',
                'id' => 'name',
            ])->required() !!}

        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Inline Radio Buttons -->
    <div x-data="{ selectedType: '{{ old('type', 'rental') }}' }">
        <label class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">
            Product Type
        </label>

        <div class="flex flex-wrap items-center gap-4">
            @foreach (['rental' => 'Rental Configuration', 'retail' => 'Retail Configuration'] as $value => $label)
                <label
                    :class="selectedType === '{{ $value }}'
                        ?
                        'text-gray-700 dark:text-gray-200' :
                        'text-gray-500 dark:text-gray-400'"
                    class="relative inline-flex items-center gap-2 text-sm font-medium cursor-pointer select-none">
                    {!! html()->radio('type', false, $value)->id($value)->attributes([
                            'x-model' => 'selectedType',
                            'class' => 'sr-only',
                        ]) !!}

                    <span
                        :class="selectedType === '{{ $value }}'
                            ?
                            'border-brand-500 bg-brand-500' :
                            'bg-transparent border-gray-300 dark:border-gray-700'"
                        class="flex h-5 w-5 items-center justify-center rounded-full border-[1.25px]">
                        <span :class="selectedType === '{{ $value }}' ? 'block' : 'hidden'"
                            class="w-2 h-2 bg-white rounded-full"></span>
                    </span>
                    {{ $label }}
                </label>
            @endforeach
        </div>

        @error('type')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Short Content --}}
<div class="mb-8">
    <label for="short_content" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Short
        Content</label>
    {{ html()->textarea('short_content')->class([
            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
            'border-red-500' => $errors->has('short_content'),
        ])->attributes([
            'rows' => 3,
            'maxlength' => 1000,
            'data-parsley-maxlength' => 1000,
            'autocomplete' => 'off',
            'placeholder' => 'Enter short description of the product',
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
        ]) }}
    @error('content')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>


<!-- Media Upload -->
<div x-data="imagePreviewHandler()" class="space-y-3">
    <label for="images" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
        Product Images
    </label>

    {!! html()->file('images[]')->attributes([
            'id' => 'images',
            'multiple' => true,
            'accept' => 'image/*',
            'x-ref' => 'fileInput',
            '@change' => 'previewImages($event)',
            'class' =>
                'block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 file:font-semibold hover:file:bg-blue-100 dark:file:bg-gray-800 dark:file:text-white dark:hover:file:bg-gray-700',
        ]) !!}

    <!-- Image Previews -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3" x-show="images.length">
        <template x-for="(image, index) in images" :key="index">
            <div class="relative">
                <img :src="image"
                    class="w-full h-32 object-cover rounded border border-gray-300 dark:border-gray-700" />
                <button type="button" @click="removeImage(index)"
                    class="absolute top-1 right-1 bg-red-600 hover:bg-red-700 rounded-full p-1 text-white shadow-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                </button>
            </div>
        </template>
    </div>

    @error('images')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

{{-- First Row: SKU + Barcode (equal width) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
    {{-- SKU --}}
    <div>
        <label for="sku" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">SKU</label>
        {!! html()->text('sku')
            ->attributes([
                'placeholder' => 'Enter SKU',
                'autocomplete' => 'off',
                'id' => 'sku',
            ])
            ->class('w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 border-gray-300 dark:bg-gray-900 dark:text-white') !!}
    </div>

    {{-- Barcode --}}
    <div>
        <label for="barcode" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Barcode (ISBN, UPC, GTIN, etc.)</label>
        {!! html()->text('barcode')
            ->attributes([
                'placeholder' => 'Enter barcode',
                'autocomplete' => 'off',
                'id' => 'barcode',
            ])
            ->class('w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 border-gray-300 dark:bg-gray-900 dark:text-white') !!}
    </div>
</div>

{{-- Second Row: Price, Sale Price, Product Cost --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 ">
    {{-- Price --}}
    <div class="lg:col-span-4">
        <label for="price" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Price</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400 text-sm">$</span>
            {!! html()->text('price')
                ->attributes([
                    'placeholder' => '0',
                    'autocomplete' => 'off',
                    'id' => 'price',
                ])
                ->class('pl-6 w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 border-gray-300 dark:bg-gray-900 dark:text-white') !!}
        </div>
    </div>

    {{-- Sale Price --}}
    <div class="lg:col-span-4">
        <label for="sale_price" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Sale Price</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400 text-sm">$</span>
            {!! html()->text('sale_price')
                ->attributes([
                    'placeholder' => '0',
                    'autocomplete' => 'off',
                    'id' => 'sale_price',
                ])
                ->class('pl-6 w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 border-gray-300 dark:bg-gray-900 dark:text-white') !!}
        </div>
    </div>

    {{-- Product Cost --}}
    <div class="lg:col-span-4">
        <label for="product_cost" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Product Cost</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400 text-sm">$</span>
            {!! html()->text('product_cost')
                ->attributes([
                    'placeholder' => '0',
                    'autocomplete' => 'off',
                    'id' => 'product_cost',
                ])
                ->class('pl-6 w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 border-gray-300 dark:bg-gray-900 dark:text-white') !!}
        </div>
    </div>
</div>

@include('admin.product_management.products.partials._rental_delivery_settings')

<!-- Product Options Placeholder -->
@includeIf('admin.product_management.products.partials._options', ['options' => $options ?? []])

<div x-data="relatedProducts()">
    @include('admin.product_management.products.partials._related_products', ['relatedProducts' => $relatedProducts ?? []])
</div>

<div x-data="{ type: '{{ old('type', 'rental') }}', showTermSelect: false }"
     x-init="$watch('type', value => { if (value !== 'rental') showTermSelect = false })">
    @include('admin.product_management.products.partials._terms_checklist')
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

    <div id="seo-fields" class="mt-4 space-y-4 hidden">
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
        function imagePreviewHandler() {
            return {
                images: [],
                previewImages(event) {
                    this.images = []
                    const files = event.target.files
                    if (!files) return

                    for (let i = 0; i < files.length; i++) {
                        const reader = new FileReader()
                        reader.onload = e => this.images.push(e.target.result)
                        reader.readAsDataURL(files[i])
                    }
                },
                removeImage(index) {
                    this.images.splice(index, 1)
                    // Optional: remove file from input (not fully supported across browsers)
                    const dt = new DataTransfer()
                    const input = this.$refs.fileInput
                    const files = input.files

                    for (let i = 0; i < files.length; i++) {
                        if (i !== index) {
                            dt.items.add(files[i])
                        }
                    }
                    input.files = dt.files
                    this.previewImages({
                        target: input
                    })
                }
            }
        }
    </script>
@endpush

<!-- Basic Info -->
<div class="grid grid-cols-3 gap-6">
    <!-- Title Input -->
    <div>
        <label for="title" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
            Title
        </label>

        {!! html()->text('title', old('title', $terms->title ?? null))->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                'border-gray-300' => !$errors->has('title'),
                'border-red-500' => $errors->has('title'),
            ])->attributes([
                'maxlength' => 240,
                'data-parsley-maxlength' => 240,
                'placeholder' => 'Enter Title',
                'autocomplete' => 'off',
                'id' => 'title',
            ])->required() !!}

        @error('title')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status"
            class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Status</label>
        {{ html()->select(
                'status',
                ['Published' => 'Published', 'Draft' => 'Draft', 'Pending' => 'Pending'],
                old('status', $terms->status ?? 'Published'),
            )->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('status'),
            ])->required()->placeholder('Please Select') }}
        @error('status')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Inline Label Display -->
    <div x-data x-init="$store.termForm.selectedType = '{{ old('is_global', $terms->is_global ?? 'No') }}'">
        <label class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">
            Term Type
        </label>

        <div class="mb-4 text-sm font-medium text-gray-700 dark:text-gray-200">
            <template x-if="$store.termForm.selectedType === 'Yes'">
                <span
                    class="inline-flex items-center px-3 py-1 rounded bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                    Global
                </span>
            </template>
            <template x-if="$store.termForm.selectedType !== 'Yes'">
                <span
                    class="inline-flex items-center px-3 py-1 rounded bg-yellow-100 text-yellow-700 dark:bg-yellow-400 dark:text-yellow-100">
                    Product
                </span>
            </template>
        </div>
        <input type="hidden" name="is_global" x-model="$store.termForm.selectedType">
    </div>

</div>

@if (isset($terms) && $terms->is_global == 'Yes')
    <button type="button" id="insert-shortcode-product-initials-btn"
        class="mb-3 px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded hover:bg-brand-700">
        Add Product Terms
    </button>
@else
    <button type="button" id="insert-shortcode-customer-initials-btn"
        class="mb-3 px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded hover:bg-brand-700">
        Add Customer Approval
    </button>
@endif

{{-- Content --}}
<div class="mb-8">
    <label for="content"
        class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Description</label>
    {{ html()->textarea('content', $terms->content ?? null)->class(
            'tinymce w-full min-h-[300px] rounded-md border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white border-gray-300 focus:ring-brand-500 focus:border-brand-500',
        )->attributes([
            'autocomplete' => 'off',
            'id' => 'content-editor',
            'placeholder' => 'Enter Description Of The Terms',
        ]) }}
    @error('content')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

@if (isset($terms) && $terms->is_global == 'Yes')
<div class="flex gap-3 mb-3">
    <button type="button" id="insert-shortcode-customer-name-btn"
        class="px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded hover:bg-brand-700">
        Add Customer Name
    </button>
    <button type="button" id="insert-shortcode-customer-signature-btn"
        class="px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded hover:bg-brand-700">
        Add Customer Signature
    </button>
</div>
{{-- Signature --}}
<div class="mb-8">
    <label for="signature_block"
        class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Signature Block</label>
    {{ html()->textarea('signature_block', $terms->signature_block ?? null)->class(
            'tinymce w-full min-h-[300px] rounded-md border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white border-gray-300 focus:ring-brand-500 focus:border-brand-500',
        )->attributes([
            'autocomplete' => 'off',
            'id' => 'signature-editor',
            'placeholder' => 'Enter Signature',
        ])->required() }}
    @error('signature_block')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
@endif

{{-- SEO Meta Section --}}
<div class="border border-gray-200 rounded-md p-4 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 mb-6">
    <div class="flex justify-between items-center mb-2">
        <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Search Engine Optimize</span>
        <a href="#" onclick="document.getElementById('seo-fields').classList.toggle('hidden'); return false;"
            class="text-sm text-blue-600 hover:underline">Edit SEO meta</a>
    </div>

    <div class="text-sm text-gray-800 dark:text-white">
        <p class="text-blue-600 font-semibold truncate">
            {{ old('seo_title', $terms->seo_title ?? 'Sample Term Title') }}</p>
        <p class="text-green-700 text-xs truncate">
            {{ url('/term-type') }}/{{ old('slug', $terms->slug ?? 'Sample Term Type') }}
        </p>
        <p class="text-gray-700 dark:text-gray-300 mt-1">
            {{ old('seo_description', $terms->seo_description ?? 'Terms Description') }}
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
    <script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
    @vite('resources/admin/js/tinymce.js')
    <script>
        document.addEventListener('alpine:init', () => {
            // Alpine store for shared state
            Alpine.store('termForm', {
                selectedType: '{{ old('type', 'No') }}'
            });

            // Watch and reset no inputs when switching away
            Alpine.effect(() => {
                if (Alpine.store('termForm').selectedType !== 'No') {
                    const rentalSection = document.querySelector(
                        '[x-show="$store.termForm.selectedType === \'No\'"]');

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

        document.addEventListener('DOMContentLoaded', () => {
            const insertProductBtn = document.getElementById('insert-shortcode-product-initials-btn');
            if (insertProductBtn) {
                insertProductBtn.addEventListener('click', () => {
                    const editor = tinymce.get('content-editor');
                    const shortcode = '[product_terms][/product_terms]';
                    if (editor) {
                        const content = editor.getContent();
                        if (content.includes(shortcode)) {
                            notyf.error('Product Terms shortcode already added.');
                        } else {
                            editor.insertContent(shortcode);
                        }
                    } else {
                        console.warn('TinyMCE editor not ready.');
                    }
                });
            }

            const insertCustomerBtn = document.getElementById('insert-shortcode-customer-initials-btn');
            if (insertCustomerBtn) {
                insertCustomerBtn.addEventListener('click', () => {
                    const editor = tinymce.get('content-editor');
                    if (editor) {
                        editor.insertContent('[customer_approval][/customer_approval]');
                    } else {
                        console.warn('TinyMCE editor not ready.');
                    }
                });
            }

            const insertCustomerNameBtn = document.getElementById('insert-shortcode-customer-name-btn');
            if (insertCustomerNameBtn) {
                insertCustomerNameBtn.addEventListener('click', () => {
                    const editor = tinymce.get('signature-editor');
                    const shortcode = '[customer_name][/customer_name]';
                    if (editor) {
                        const content = editor.getContent();
                        if (content.includes(shortcode)) {
                            notyf.error('Customer Name shortcode already added.');
                        } else {
                            editor.insertContent(shortcode);
                        }
                    } else {
                        console.warn('TinyMCE editor not ready.');
                    }
                });
            }

            const insertCustomerSignatureBtn = document.getElementById('insert-shortcode-customer-signature-btn');
            if (insertCustomerSignatureBtn) {
                insertCustomerSignatureBtn.addEventListener('click', () => {
                    const editor = tinymce.get('signature-editor');
                    if (editor) {
                        const content = editor.getContent();
                        const shortcode = '[customer_signature][/customer_signature]';
                        if (content.includes(shortcode)) {
                            notyf.error('Customer Signature shortcode already added.');
                        } else {
                            editor.insertContent(shortcode);
                        }
                    } else {
                        console.warn('TinyMCE editor not ready.');
                    }
                });
            }
        });
    </script>
@endpush


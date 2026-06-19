@php
    $isGeneral = old('is_general_term_type', isset($objProduct) ? $objProduct->is_general_term_type : true);
    $isCustom = old('is_custom_term_type', isset($objProduct) ? $objProduct->is_custom_term_type : false);
    $selectedTerms = isset($objProduct) ? $objProduct->terms()->pluck('terms_and_condition_id')->toArray() : [];
@endphp

<!-- Terms -->
<div class="">
    <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-2">Terms</h4>

    <div class="flex items-center gap-4">
        <label class="inline-flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" name="is_general_term_type" value="1" id="is_general_term_type"
                {{ $isGeneral ? 'checked' : '' }}
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700"
                data-parsley-multiple="term_type"
                x-bind:data-parsley-required="$store.productForm.selectedType === 'Rental'"
                data-parsley-required-message="Please select at least one option."
                data-parsley-errors-container="#terms-error"
                data-parsley-class-handler="#terms-group"
                />
            <span>General Terms</span>
        </label>

        <label class="inline-flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" name="is_custom_term_type" value="1" id="is_custom_term_type"
                {{ $isCustom ? 'checked' : '' }}
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700"
                data-parsley-multiple="term_type"
                data-parsley-required-message="Please select at least one option."
                data-parsley-errors-container="#terms-error"
                data-parsley-class-handler="#terms-group"
                />
            <span>Add Product Specific Terms</span>
        </label>
    </div>
    <!-- Single shared error spot -->
    <div id="terms-error" class="terms-error mt-1 text-sm text-red-600 dark:text-red-400"></div>
    @error('is_general_term_type')
        <div class="terms-error mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror
    @error('is_custom_term_type')
        <div class="terms-error mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

    <!-- Show this only if Custom is selected -->
    <div id="termsSelect" style="display: {{ $isCustom ? 'block' : 'none' }};">
        {!! html()->select('terms', $terms, old('terms', $selectedTerms ?? []))->class(
                'choices-select mt-2 w-full rounded border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-700'
            )->attribute('id', 'terms')
            ->attribute('data-parsley-errors-container','#terms-errors')
            ->attributes($terms->isEmpty() ? ['disabled' => true] : []) !!}
        <div id="terms-errors"></div>
        @error('terms')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const general = document.getElementById('is_general_term_type');
            const custom = document.getElementById('is_custom_term_type');
            const termsSelect = document.getElementById('termsSelect');
            const termsField = document.getElementById('terms');

            function updateTermsDisplay() {
                if (custom.checked) {
                    termsSelect.style.display = 'block';
                    termsField.setAttribute('required', 'required');
                } else {
                    termsSelect.style.display = 'none';
                    termsField.removeAttribute('required');
                }
            }

            general.addEventListener('change', function() {
                if (!general.checked) {
                    custom.checked = false;
                }
                updateTermsDisplay();
            });

            custom.addEventListener('change', function() {
                if (custom.checked) {
                    general.checked = true;
                }
                updateTermsDisplay();
            });

            // Initialize display on load
            updateTermsDisplay();
        });
    </script>
@endpush

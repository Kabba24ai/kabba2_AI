@php
    $isGeneral = old('is_general_term_type', isset($objProduct) ? $objProduct->is_general_term_type : true);
    $isCustom = old('is_custom_term_type', isset($objProduct) ? $objProduct->is_custom_term_type : false);
    $selectedTerms = isset($objProduct) ? $objProduct->terms()->pluck('terms_and_condition_id')->toArray() : [];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <!-- Terms -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4 space-y-3">
        <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-2">Terms</h4>

        <div class="flex items-center gap-4">
            <label class="inline-flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="is_general_term_type" value="1" id="is_general_term_type"
                    {{ $isGeneral ? 'checked' : '' }}
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700" />
                <span>General Terms</span>
            </label>

            <label class="inline-flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="is_custom_term_type" value="1" id="is_custom_term_type"
                    {{ $isCustom ? 'checked' : '' }}
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700" />
                <span>Add Product Specific Terms</span>
            </label>
        </div>

        <!-- Show this only if Custom is selected -->
        <div id="termsSelect" style="display: {{ $isCustom ? 'block' : 'none' }};">
            {!! html()->select('terms', $terms, old('terms', $selectedTerms ?? []))->class(
                    'choices-select mt-2 w-full rounded border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-700'
                )->attribute('id', 'terms')
                ->attribute('data-parsley-required', $isCustom ? 'true' : 'false')
                ->attribute('data-parsley-errors-container','#terms-errors')
                ->attributes($terms->isEmpty() ? ['disabled' => true] : []) !!}
            <div id="terms-errors"></div>
        </div>
    </div>

    <!-- Checklist -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4">
        <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-2">Checklist</h4>
        <select name="checklist_id"
            class="w-full rounded border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-700">
            <option value="">Select Checklist</option>
            <option value="1">Checklist A</option>
            <option value="2">Checklist B</option>
        </select>
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

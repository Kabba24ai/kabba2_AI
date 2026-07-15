@extends('admin.layouts.app')

@section('title', 'Create Product')

@push('css')
@endpush

@section('content')

    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Add New Product</h3>
        <div class="flex items-center space-x-4">
            <!-- Save -->
            <button type="button" name="action" value="save"
                class="submit-btn inline-flex items-center px-6 py-2 rounded-md text-white bg-teal-600 hover:bg-teal-700 text-sm font-semibold shadow transition">
                Save
                <x-heroicon-o-check class="w-4 h-4 ml-2" />
            </button>

            <!-- Save As New Button -->
            <button type="button" name="action" value="save_new"
                class="submit-btn inline-flex items-center px-6 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition">
                Save As New
                <x-heroicon-o-plus class="w-4 h-4 ml-2" />
            </button>

            <!-- Save & Exit Button -->
            <button type="button" name="action" value="save_exit"
                class="submit-btn inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition">
                Save & Exit
                <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 ml-2" />
            </button>
        </div>
    </div>

    @include('flash::message')
    @include('admin.partials.formErrors')

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-4 py-4">


                    <!-- Product Form -->
                    {{ html()->form()->attributes([
                            'action' => route('admin.product-management.products.create'),
                            'id' => 'productForm',
                            'method' => 'POST',
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                    @include('admin.product_management.products.partials._form')

                    <div class="flex justify-center mt-8 space-x-4">
                        <!-- Save As Dropdown -->
                        {{-- <div x-data="{ open: false }" class="relative flex items-center">
                            <button type="button" @click="open = !open"
                                class="inline-flex items-center px-6 py-2 rounded-md text-white bg-teal-600 hover:bg-teal-700 text-sm font-semibold shadow transition">
                                Save As
                                <x-heroicon-o-chevron-right class="w-4 h-4 ml-2" />
                            </button>

                            <!-- Horizontal Dropdown (Flyout) -->
                            <div x-show="open" @click.away="open = false" x-transition
                                class="absolute left-full top-1/2 -translate-y-1/2 ml-2  w-36 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg z-50 text-left">
                                <!-- Draft -->
                                <button type="submit" name="action" value="save" @click="$refs.statusField.value = 'Draft'; open = false"
                                    class="w-full px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                    Draft
                                </button>

                                <!-- Pending -->
                                <button type="submit" name="action" value="save" @click="$refs.statusField.value = 'Pending'; open = false"
                                    class="w-full px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                    Pending
                                </button>

                                <!-- Published -->
                                <button type="submit" name="action" value="save" @click="$refs.statusField.value = 'Published'; open = false"
                                    class="w-full px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                    Published
                                </button>
                            </div>
                        </div> --}}

                        <!-- Save -->
                        <button type="button" name="action" value="save"
                            class="submit-btn inline-flex items-center px-6 py-2 rounded-md text-white bg-teal-600 hover:bg-teal-700 text-sm font-semibold shadow transition">
                            Save
                            <x-heroicon-o-check class="w-4 h-4 ml-2" />
                        </button>

                        <!-- Save As New Button -->
                        <button type="button" name="action" value="save_new"
                            class="submit-btn inline-flex items-center px-6 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition">
                            Save As New
                            <x-heroicon-o-plus class="w-4 h-4 ml-2" />
                        </button>

                        <!-- Save & Exit Button -->
                        <button type="button" name="action" value="save_exit"
                            class="submit-btn inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition">
                            Save & Exit
                            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 ml-2" />
                        </button>
                    </div>


                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const parsleyForm = $('#productForm').parsley(); // requires jQuery + Parsley jQuery adapter

            // All six delivery tiers share the readonly toggle and error clearing
            const feeInputs = [
                'standard_delivery_fee', 'extended_delivery_fee',
                'custom_1_delivery_fee', 'custom_2_delivery_fee',
                'custom_3_delivery_fee', 'custom_4_delivery_fee'
            ].map(name => document.querySelector('input[name="' + name + '"]')).filter(Boolean);
            const delCheck  = document.querySelector('input[name="delivery_and_pickup"]');
            const errorBox  = document.getElementById('delivery-fee-error');

            // helper to toggle readonly + subtle styling
            function setFeesReadonly(isReadonly) {
                feeInputs.forEach(el => {
                    if (isReadonly) {
                        el.value = "";
                        el.readOnly = true;                        // readonly attr
                        el.classList.add('readonly'); // add class
                    } else {
                        el.readOnly = false;
                        el.classList.remove('readonly');
                    }
                });
            }

            // initial state on load
            if (delCheck) setFeesReadonly(!delCheck.checked);

            // update on change
            if (delCheck) {
                delCheck.addEventListener('change', () => {
                    setFeesReadonly(!delCheck.checked);
                    // clear any shared error when user toggles the option
                    if (errorBox) errorBox.textContent = '';
                });
            }

            // live error clearing when user types
            if (feeInputs.length && errorBox) {
                feeInputs.forEach(el => {
                    el.addEventListener('input', () => (errorBox.textContent = ''));
                });
            }


            // AJAX submit method
            function handleProductSubmit(clickedButtonValue = null) {
                const form = document.getElementById('productForm');
                const productType = document.querySelector('input[name="product_type"]:checked')?.value;
                const isFormValid = parsleyForm.validate({
                    force: true
                });
                // Only enforce delivery-fee rule when "Truck Delivery / Pickup" is selected
                const deliveryEnabled = !!delCheck?.checked;

                // Six-tier rule (matches the server): at least one delivery fee
                // is CONFIGURED (non-empty) — an explicit 0 counts as configured
                if (deliveryEnabled && feeInputs.length && errorBox && productType === 'Rental') {
                    const hasConfiguredFee = feeInputs.some(el => el.value.trim() !== '');

                    if (!hasConfiguredFee) {
                        errorBox.textContent = 'At least one delivery fee (Standard, Extended, or Custom) must be configured.';
                        feeInputs[0].focus();
                        return; // Stop submission
                    } else {
                        errorBox.textContent = '';
                    }
                }
                // --------------------------------------------------

                // Force Parsley to validate
               if (!isFormValid) {
                    const failedFields = parsleyForm.fields.filter(field => !field.isValid());

                    failedFields.forEach(field => {
                        console.log('Failed field:', field.$element.attr('name'));
                    });

                    return;
                }

                // Disable all submit and header buttons and change their text
                const submitButtons = document.querySelectorAll('.submit-btn');
                const originalTexts = new Map();

                submitButtons.forEach(btn => {
                    originalTexts.set(btn, btn.innerHTML);
                    btn.innerHTML = 'Saving...';
                    btn.disabled = true;
                });

                const formData = new FormData(form);
                formData.append('method', 'PUT'); // important
                if (clickedButtonValue) {
                    formData.append('action', clickedButtonValue);
                }

                const action = form.getAttribute('action');

                try {
                    apiFetch(action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: formData
                    })
                    .then(res => {
                        if (res && res.success) {
                            notyf.success(res.message);
                            if (res.action === 'save_exit' || res.redirect_url) {
                                setTimeout(() => {
                                    window.location.href = res.redirect_url;
                                }, 800);
                            } else if (res.action === 'save_new') {
                                window.location.href = "{{ route('admin.product-management.products.create') }}";
                            } else {
                                window.location.reload();
                            }
                        } else {
                            notyf.error(res.message);
                        }
                    })
                    .finally(() => {
                        submitButtons.forEach(btn => {
                            btn.disabled = false;
                            btn.innerHTML = originalTexts.get(btn);
                        });
                    });
                } catch (error) {
                    console.error('Submission failed:', error);
                }
            }

            // Header buttons trigger AJAX submit
            document.querySelectorAll('.submit-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    handleProductSubmit(btn.value);
                });
            });

        });
    </script>
@endpush

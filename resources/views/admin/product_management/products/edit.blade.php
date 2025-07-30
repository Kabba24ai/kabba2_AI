@extends('admin.layouts.app')

@section('title', 'Edit Product')

@push('css')
@endpush

@section('content')

    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Edit Product</h3>
    </div>

    @include('flash::message')
    @include('admin.partials.formErrors')
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-4 py-4">

                    <!-- Product Form -->
                    {{ html()->modelForm($objProduct, 'PUT')->attributes([
                            'action' => route('admin.product-management.products.edit', $objProduct->unique_id),
                            'id' => 'productForm',
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
                        <button type="submit" name="action" value="save"
                            class="inline-flex items-center px-6 py-2 rounded-md text-white bg-teal-600 hover:bg-teal-700 text-sm font-semibold shadow transition">
                            Save
                            <x-heroicon-o-check class="w-4 h-4 ml-2" />
                        </button>

                        <!-- Save & New Button -->
                        <button type="submit" name="action" value="save_new"
                            class="inline-flex items-center px-6 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition">
                            Save & New
                            <x-heroicon-o-plus class="w-4 h-4 ml-2" />
                        </button>

                        <!-- Save & Exit Button -->
                        <button type="submit" name="action" value="save_exit"
                            class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition">
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
            const form = document.getElementById('productForm');
            // Live error clearing when user types
            const stdInput = document.querySelector('input[name="standard_delivery_fee"]');
            const extInput = document.querySelector('input[name="extended_delivery_fee"]');
            const errorBox = document.getElementById('delivery-fee-error');

            if (stdInput && extInput && errorBox) {
                [stdInput, extInput].forEach(el => {
                    el.addEventListener('input', () => {
                        errorBox.textContent = '';
                    });
                });
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const parsleyForm = $(form).parsley();

                // --- CUSTOM COMMON VALIDATION FOR DELIVERY FEES ---
                if (stdInput && extInput && errorBox) {
                    const stdVal = parseFloat(stdInput.value || 0);
                    const extVal = parseFloat(extInput.value || 0);

                    if (stdVal <= 0 && extVal <= 0) {
                        errorBox.textContent =
                            'At least one delivery fee (standard or extended) must be greater than zero.';
                        stdInput.focus();
                        return; // Stop submission
                    } else {
                        errorBox.textContent = '';
                    }
                }
                // --------------------------------------------------

                // Force Parsley to validate
                if (!parsleyForm.isValid({
                        force: true
                    })) {
                    // Stop everything: do not show loader
                    e.preventDefault();
                    return;
                }

                const submitter = e.submitter;
                const clickedButton = submitter ? submitter.value : null;

                const submitButtons = form.querySelectorAll('button[type="submit"]');
                // Store original button texts
                const originalTexts = new Map();
                submitButtons.forEach(btn => {
                    originalTexts.set(btn, btn.innerHTML);
                    btn.innerHTML = 'Saving...';
                    btn.disabled = true;
                });

                const formData = new FormData(form);

                formData.append('method', 'PUT'); // important

                // Include clicked button value
                if (clickedButton) {
                    formData.append('action', clickedButton);
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

                                // If redirect_url is provided by the backend, navigate there
                                if (res.action === 'save_exit' || res.redirect_url) {
                                    setTimeout(() => {
                                        window.location.href = res.redirect_url;
                                    }, 800);
                                } else if (res.action === 'save_new') {
                                    // If saving new, redirect to create page
                                    window.location.href = "{{ route('admin.product-management.products.create') }}";
                                } else {
                                    // Otherwise, stay on the edit page
                                    window.location.reload();
                                }

                            } else {
                                notyf.error(res.message);
                            }

                        })
                        .finally(() => {
                            // Restore buttons
                            submitButtons.forEach(btn => {
                                btn.disabled = false;
                                btn.innerHTML = originalTexts.get(btn);
                            });
                        });


                } catch (error) {
                    console.error('Submission failed:', error);
                }
            });
        });
    </script>
@endpush

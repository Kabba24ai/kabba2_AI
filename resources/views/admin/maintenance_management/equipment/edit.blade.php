@extends('admin.layouts.app')

@section('title', 'Update Equipment')

@section('content')
    <div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
        <div class="flex-1">
            {{-- Header --}}
            <div class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="max-w-5xl flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('admin.maintenance-management.equipment.index') }}"
                            class="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Back to Equipment</span>
                        </a>
                        <div class="h-6 border-l border-gray-300"></div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Equipment Details</h1>
                            <p class="text-sm text-gray-600">Edit equipment information</p>
                        </div>
                    </div>

                    <button type="submit" form="productForm" name="save_as_new" value="1"
                        class="inline-flex items-center px-5 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition">
                        Save as New
                        <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="p-6">
                {{ html()->modelForm($equipment, 'PUT')->attributes([
                        'action' => route('admin.maintenance-management.equipment.edit', $equipment->unique_id),
                        'id' => 'productForm',
                        'autocomplete' => 'off',
                        'data-parsley-validate' => true,
                        'class' => 'max-w-5xl',
                    ])->acceptsFiles()->open() }}
                @csrf
                @method('PUT')

                {{-- Main actions --}}
                <div class="mb-6 flex justify-end gap-3">
                    <button type="submit" name="action" value="save"
                        class="inline-flex items-center px-6 py-2 rounded-md border border-blue-600 text-blue-600 bg-white hover:bg-blue-50 text-sm font-semibold shadow-sm transition">
                        Save
                    </button>

                    <button type="submit" name="action" value="save_exit"
                        class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition">
                        Save &amp; Exit
                        <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9">
                            </path>
                        </svg>
                    </button>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 pt-5">
                        <nav class="-mb-px flex flex-wrap gap-6" aria-label="Equipment tabs">
                            <button type="button" data-tab-btn="overview"
                                class="tab-btn inline-flex items-center border-b-2 border-blue-600 px-1 pb-3 text-sm font-semibold text-blue-600 transition-colors">
                                Overview
                            </button>
                            <button type="button" data-tab-btn="specification"
                                class="tab-btn inline-flex items-center border-b-2 border-transparent px-1 pb-3 text-sm font-semibold text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-700">
                                Specification
                            </button>
                            <button type="button" data-tab-btn="key-comparison"
                                class="tab-btn inline-flex items-center border-b-2 border-transparent px-1 pb-3 text-sm font-semibold text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-700">
                                Key Comparison
                            </button>
                        </nav>
                    </div>

                    <div class="p-6 max-h-[calc(100vh-270px)] overflow-y-auto">
                        <div data-tab-panel="overview">
                            @include('admin.maintenance_management.equipment.partials._form')
                        </div>

                        <div data-tab-panel="specification" class="hidden">
                            <div class="max-w-4xl space-y-6">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Specification</h2>
                                    <p class="mt-1 text-sm text-gray-600">Use the saved equipment data as context, then
                                        refine or generate a specification with AI.</p>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                                    <label for="equipment_specification_ai"
                                        class="mb-2 block text-sm font-medium text-gray-700">
                                        Specification Notes
                                    </label>
                                    <textarea id="equipment_specification_ai" rows="14"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Use the existing equipment details to draft a complete specification sheet."></textarea>

                                    <div class="mt-4 flex justify-end">
                                        <button type="button"
                                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            <span>AI Generate</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div data-tab-panel="key-comparison" class="hidden">
                            <div class="max-w-4xl space-y-6">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Key Comparison</h2>
                                    <p class="mt-1 text-sm text-gray-600">Build a comparison using the current equipment
                                        data and other relevant products.</p>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 space-y-4">
                                    <div>
                                        <label for="equipment_key_comparison_notes"
                                            class="mb-2 block text-sm font-medium text-gray-700">
                                            Comparison Notes
                                        </label>
                                        <textarea id="equipment_key_comparison_notes" name="equipment_key_comparison_notes" rows="12"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Describe what should be compared for this equipment.">{{ old('equipment_key_comparison_notes') }}</textarea>
                                    </div>

                                    <div>
                                        <label for="similar_equipment_ids"
                                            class="mb-2 block text-sm font-medium text-gray-700">
                                            Similar Products (from Equipment Name)
                                        </label>
                                        @php
                                            $selectedSimilarEquipmentIds = array_map('strval', old('similar_equipment_ids', $defaultSimilarEquipmentIds ?? []));
                                        @endphp
                                        <select id="similar_equipment_ids" name="similar_equipment_ids[]" multiple
                                            class="choices-select w-full rounded-md border border-gray-300 bg-white text-sm text-gray-900">
                                            @forelse($similarEquipmentOptions as $id => $label)
                                                <option value="{{ $id }}"
                                                    @selected(in_array((string) $id, $selectedSimilarEquipmentIds, true))>
                                                    {{ $label }}
                                                </option>
                                            @empty
                                                <option value="" disabled>No similar equipment found in this category</option>
                                            @endforelse
                                        </select>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="button"
                                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            <span>AI Generate</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const buttons = Array.from(document.querySelectorAll('[data-tab-btn]'));
            const panels = Array.from(document.querySelectorAll('[data-tab-panel]'));

            if (!buttons.length || !panels.length) {
                return;
            }

            function setActive(tabName) {
                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== tabName);
                });

                buttons.forEach((button) => {
                    const isActive = button.getAttribute('data-tab-btn') === tabName;

                    button.classList.toggle('border-blue-600', isActive);
                    button.classList.toggle('text-blue-600', isActive);
                    button.classList.toggle('border-transparent', !isActive);
                    button.classList.toggle('text-gray-500', !isActive);
                });
            }

            buttons.forEach((button) => {
                button.addEventListener('click', () => setActive(button.getAttribute('data-tab-btn')));
            });

            setActive('overview');
        });
    </script>
@endpush

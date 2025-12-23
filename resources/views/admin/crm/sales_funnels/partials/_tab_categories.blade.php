<div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Category Management</h2>
                        <p class="mt-1 text-sm text-slate-500">Organize your sales funnels with categories</p>
                    </div>

                    <button type="button" data-open-category-modal
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Category
                    </button>

                    {{-- Modal --}}
                    <div id="categoryModal" class="fixed inset-0 z-9999 hidden">
                        {{-- Overlay --}}
                        <div data-close-category-modal class="absolute inset-0 bg-black/40"></div>

                        {{-- Dialog --}}
                        <div class="relative mx-auto flex min-h-screen max-w-lg items-center justify-center px-4">
                            <div class="w-full overflow-hidden rounded-2xl bg-white shadow-xl" role="dialog"
                                aria-modal="true" aria-labelledby="categoryModalTitle">
                                {{-- Header --}}
                                <div class="flex items-center justify-between px-6 py-4">
                                    <h3 id="categoryModalTitle" class="text-lg font-semibold text-slate-900">
                                        Add Category
                                    </h3>
                                    <button type="button" data-close-category-modal
                                        class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="h-px bg-slate-200"></div>

                                {{-- Body --}}
                                {{ html()->form()->attributes([
                                        'data-parsley-validate' => true,
                                        'class' => 'px-6 py-5',
                                        'id' => 'categoryForm',
                                    ])->open() }}
                                    {{-- Category Name --}}
                                    <div>
                                        <label class="text-sm font-medium text-slate-700 required" for="categoryName">
                                            Category Name
                                        </label>
                                        <input type="text" name="name" id="categoryName"
                                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                            placeholder="Enter category name" required />
                                    </div>

                                    {{-- Description --}}
                                    <div class="mt-4">
                                        <label class="text-sm font-medium text-slate-700">Description</label>
                                        <textarea name="description" rows="4" id="description"
                                            class="mt-2 w-full resize-none rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                            placeholder="Enter description"></textarea>
                                    </div>

                                    {{-- Color Picker --}}
                                    <div class="mt-4">
                                        <label class="text-sm font-medium text-slate-700">Color <span
                                                class="text-red-500">*</span></label>

                                        <input type="hidden" name="color" id="categoryColor" value="#34d399"
                                            required />

                                        <div class="mt-3 grid grid-cols-4 gap-3">
                                            @php
                                                $colors = [
                                                    '#3b82f6', // blue
                                                    '#34d399', // green
                                                    '#f59e0b', // orange
                                                    '#ef4444', // red
                                                    '#8b5cf6', // purple
                                                    '#ec4899', // pink
                                                    '#2dd4bf', // teal
                                                    '#6366f1', // indigo
                                                ];
                                            @endphp

                                            @foreach ($colors as $c)
                                                <button type="button" data-color-btn data-color="{{ $c }}"
                                                    class="relative h-12 w-full rounded-lg"
                                                    style="background: {{ $c }};"
                                                    aria-label="Pick color {{ $c }}">
                                                    <span
                                                        class="pointer-events-none absolute inset-0 rounded-lg ring-2 ring-transparent"></span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Footer Buttons --}}
                                    <div class="mt-6 flex items-center gap-4">
                                        <button type="button" data-close-category-modal
                                            class="w-1/2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                            Cancel
                                        </button>

                                        <button type="submit"
                                            class="w-1/2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                                            Save Category
                                        </button>
                                    </div>
                                {{ html()->form()->close() }}
                            </div>
                        </div>
                    </div>

                </div>

                <div id="categories-table-wrapper" aria-live="polite" class="mt-6">
                    @include('admin.crm.sales_funnels.categories._table', ['categories' => []])
                </div>
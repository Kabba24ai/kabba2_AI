@php
    $selectedProductId = (int) old('product_id', $assignment->product_id ?? 0);
    $selectedPrimaryEquipmentPool = collect(old('primary_equipment_pool', $assignment->primary_equipment_pool ?? []))
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->map(fn ($value) => (int) $value)
        ->values()
        ->all();

    $selectedUpgradePrimary = collect(old('upgrade_path_primary', $assignment->upgrade_path_primary ?? []))
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->map(fn ($value) => (int) $value)
        ->values()
        ->all();

    $selectedUpgradeAlternate1 = collect(old('upgrade_path_alternate_1', $assignment->upgrade_path_alternate_1 ?? []))
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->map(fn ($value) => (int) $value)
        ->values()
        ->all();

    $selectedUpgradeAlternate2 = collect(old('upgrade_path_alternate_2', $assignment->upgrade_path_alternate_2 ?? []))
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->map(fn ($value) => (int) $value)
        ->values()
        ->all();

    $selectedDowngradeOption1 = collect(old('downgrade_path_option_1', $assignment->downgrade_path_option_1 ?? []))
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->map(fn ($value) => (int) $value)
        ->values()
        ->all();

    $pathCategoryMap = isset($assignment)
        ? $assignment->paths
            ->mapWithKeys(fn ($path) => [$path->path_type => (int) ($path->product_category_id ?? 0)])
            ->all()
        : [];

    $selectedCategoryId = (int) old('selected_category_id', $assignment->base_product_category_id ?? 0);
    $selectedUpgradePrimaryCategoryId = (int) old('upgrade_primary_category_id', $pathCategoryMap['upgrade_primary'] ?? 0);
    $selectedUpgradeAlt1CategoryId = (int) old('upgrade_alt1_category_id', $pathCategoryMap['upgrade_alternate_1'] ?? 0);
    $selectedUpgradeAlt2CategoryId = (int) old('upgrade_alt2_category_id', $pathCategoryMap['upgrade_alternate_2'] ?? 0);
    $selectedDowngradeOpt1CategoryId = (int) old('downgrade_opt1_category_id', $pathCategoryMap['downgrade_option_1'] ?? 0);

    if (!$selectedCategoryId && $selectedProductId) {
        $selectedProduct = $products->firstWhere('id', $selectedProductId);
        $selectedCategoryId = (int) ($selectedProduct?->categories->first()?->id ?? 0);
    }

    $productCategoryMap = $products->mapWithKeys(function ($product) {
        return [
            $product->id => $product->categories
                ->pluck('id')
                ->map(fn ($value) => (int) $value)
                ->values()
                ->all(),
        ];
    });

    $equipmentItems = $equipment->map(function ($item) {
        return [
            'id' => (int) $item->id,
            'name' => $item->equipment_name,
            'equipment_code' => $item->equipment_id,
            'category_id' => (int) ($item->product_category_id ?? 0),
            'category_name' => $item->productCategory?->title ?? 'Uncategorized',
        ];
    })->values();
@endphp

<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-2">
                <x-heroicon-o-cog-6-tooth class="w-4 h-4 mt-1 text-gray-500 dark:text-gray-400" />
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Product Selection</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Select the product you are defining assignment rules for.</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="selected_category_id" class="sr-only">Product Category</label>
                    <select id="selected_category_id" name="selected_category_id"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($selectedCategoryId === (int) $category->id)>
                                {{ $category->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="product_id" class="sr-only">Product</label>
                    <select name="product_id" id="product_id"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white @error('product_id') border-red-500 @enderror"
                        required>
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}"
                                data-category-ids='@json($productCategoryMap[$product->id] ?? [])'
                                @selected($selectedProductId === (int) $product->id)>
                                {{ $product->product_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-2">
                    <x-heroicon-o-lock-closed class="w-4 h-4 mt-1 text-gray-500 dark:text-gray-400" />
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Primary Equipment Pool</h4>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Select equipment that should be treated as primary assignment options for this product.</p>
                    </div>
                </div>
                <span id="selected-equipment-count" class="text-xs font-semibold text-brand-600 dark:text-brand-400">0 selected</span>
            </div>

            <div class="mt-5 max-w-md">
                <label for="equipment_category_filter" class="sr-only">Equipment Category</label>
                <select id="equipment_category_filter"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($selectedCategoryId === (int) $category->id)>
                            {{ $category->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-4 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden max-w-2xl">
                <div class="flex items-center gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/60">
                    <input id="select_all_equipment" type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900">
                    <label for="select_all_equipment" class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                        Select All (<span id="visible-equipment-count">0</span>)
                    </label>
                </div>

                <div id="primary-equipment-list" class="max-h-64 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                    @forelse ($equipmentItems as $item)
                        <label class="equipment-option hidden cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/40"
                            data-category-id="{{ $item['category_id'] }}">
                            <input type="checkbox"
                                name="primary_equipment_pool[]"
                                value="{{ $item['id'] }}"
                                class="equipment-checkbox h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900"
                                @checked(in_array($item['id'], $selectedPrimaryEquipmentPool, true))>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item['equipment_code'] }}</span>
                            </span>
                        </label>
                    @empty
                        <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">No equipment available.</div>
                    @endforelse
                </div>

                <div id="primary-equipment-empty" class="hidden px-4 py-6 text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">
                    No equipment found for the selected category.
                </div>
            </div>
            @error('primary_equipment_pool')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-2">
                <x-heroicon-o-arrow-up class="w-4 h-4 mt-1 text-gray-500 dark:text-gray-400" />
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Upgrade Paths</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">These define preferred upgrade paths if the primary equipment pool cannot fulfill the order.</p>
                </div>
            </div>

            <div class="mt-5 space-y-5">
                <div class="grid grid-cols-1 lg:grid-cols-[160px,1fr] gap-3 lg:gap-4">
                    <label for="upgrade_primary_category_filter" class="text-sm font-medium text-gray-700 dark:text-gray-300">Primary Upgrade</label>
                    <div class="max-w-2xl space-y-3">
                        <select id="upgrade_primary_category_filter" name="upgrade_primary_category_id" data-list-key="upgrade_primary"
                            class="path-category-select w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($selectedUpgradePrimaryCategoryId === (int) $category->id)>{{ $category->title }}</option>
                            @endforeach
                        </select>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/60">
                                <input id="select_all_upgrade_primary" type="checkbox" data-list-key="upgrade_primary"
                                    class="path-select-all h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900">
                                <label for="select_all_upgrade_primary" class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                    Select All (<span id="visible-count-upgrade_primary">0</span>)
                                </label>
                                <span id="selected-count-upgrade_primary" class="ml-auto text-xs font-semibold text-brand-600 dark:text-brand-400">0 selected</span>
                            </div>

                            <div id="list-upgrade_primary" class="path-equipment-list max-h-56 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800" data-list-key="upgrade_primary">
                                @foreach ($equipmentItems as $item)
                                    <label class="path-equipment-option hidden cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/40"
                                        data-list-key="upgrade_primary" data-category-id="{{ $item['category_id'] }}" data-equipment-id="{{ $item['id'] }}">
                                        <input type="checkbox" name="upgrade_path_primary[]" value="{{ $item['id'] }}"
                                            class="path-equipment-checkbox h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900"
                                            data-list-key="upgrade_primary" data-equipment-id="{{ $item['id'] }}"
                                            @checked(in_array($item['id'], $selectedUpgradePrimary, true))>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item['equipment_code'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div id="empty-upgrade_primary" class="hidden px-4 py-6 text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">No equipment found for the selected category.</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-[160px,1fr] gap-3 lg:gap-4">
                    <label for="upgrade_alt1_category_filter" class="text-sm font-medium text-gray-700 dark:text-gray-300">Alternate Upgrade #1</label>
                    <div class="max-w-2xl space-y-3">
                        <select id="upgrade_alt1_category_filter" name="upgrade_alt1_category_id" data-list-key="upgrade_alt1"
                            class="path-category-select w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($selectedUpgradeAlt1CategoryId === (int) $category->id)>{{ $category->title }}</option>
                            @endforeach
                        </select>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/60">
                                <input id="select_all_upgrade_alt1" type="checkbox" data-list-key="upgrade_alt1"
                                    class="path-select-all h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900">
                                <label for="select_all_upgrade_alt1" class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                    Select All (<span id="visible-count-upgrade_alt1">0</span>)
                                </label>
                                <span id="selected-count-upgrade_alt1" class="ml-auto text-xs font-semibold text-brand-600 dark:text-brand-400">0 selected</span>
                            </div>

                            <div id="list-upgrade_alt1" class="path-equipment-list max-h-56 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800" data-list-key="upgrade_alt1">
                                @foreach ($equipmentItems as $item)
                                    <label class="path-equipment-option hidden cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/40"
                                        data-list-key="upgrade_alt1" data-category-id="{{ $item['category_id'] }}" data-equipment-id="{{ $item['id'] }}">
                                        <input type="checkbox" name="upgrade_path_alternate_1[]" value="{{ $item['id'] }}"
                                            class="path-equipment-checkbox h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900"
                                            data-list-key="upgrade_alt1" data-equipment-id="{{ $item['id'] }}"
                                            @checked(in_array($item['id'], $selectedUpgradeAlternate1, true))>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item['equipment_code'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div id="empty-upgrade_alt1" class="hidden px-4 py-6 text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">No equipment found for the selected category.</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-[160px,1fr] gap-3 lg:gap-4">
                    <label for="upgrade_alt2_category_filter" class="text-sm font-medium text-gray-700 dark:text-gray-300">Alternate Upgrade #2</label>
                    <div class="max-w-2xl space-y-3">
                        <select id="upgrade_alt2_category_filter" name="upgrade_alt2_category_id" data-list-key="upgrade_alt2"
                            class="path-category-select w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($selectedUpgradeAlt2CategoryId === (int) $category->id)>{{ $category->title }}</option>
                            @endforeach
                        </select>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/60">
                                <input id="select_all_upgrade_alt2" type="checkbox" data-list-key="upgrade_alt2"
                                    class="path-select-all h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900">
                                <label for="select_all_upgrade_alt2" class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                    Select All (<span id="visible-count-upgrade_alt2">0</span>)
                                </label>
                                <span id="selected-count-upgrade_alt2" class="ml-auto text-xs font-semibold text-brand-600 dark:text-brand-400">0 selected</span>
                            </div>

                            <div id="list-upgrade_alt2" class="path-equipment-list max-h-56 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800" data-list-key="upgrade_alt2">
                                @foreach ($equipmentItems as $item)
                                    <label class="path-equipment-option hidden cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/40"
                                        data-list-key="upgrade_alt2" data-category-id="{{ $item['category_id'] }}" data-equipment-id="{{ $item['id'] }}">
                                        <input type="checkbox" name="upgrade_path_alternate_2[]" value="{{ $item['id'] }}"
                                            class="path-equipment-checkbox h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900"
                                            data-list-key="upgrade_alt2" data-equipment-id="{{ $item['id'] }}"
                                            @checked(in_array($item['id'], $selectedUpgradeAlternate2, true))>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item['equipment_code'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div id="empty-upgrade_alt2" class="hidden px-4 py-6 text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">No equipment found for the selected category.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-2">
                <x-heroicon-o-arrow-down class="w-4 h-4 mt-1 text-gray-500 dark:text-gray-400" />
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Downgrade Paths</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Downgrade options are used only when upgrade paths are not viable.</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 lg:grid-cols-[160px,1fr] gap-3 lg:gap-4">
                <label for="downgrade_option1_category_filter" class="text-sm font-medium text-gray-700 dark:text-gray-300">Downgrade Option #1</label>
                <div class="max-w-2xl space-y-3">
                    <select id="downgrade_option1_category_filter" name="downgrade_opt1_category_id" data-list-key="downgrade_opt1"
                        class="path-category-select w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($selectedDowngradeOpt1CategoryId === (int) $category->id)>{{ $category->title }}</option>
                        @endforeach
                    </select>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="flex items-center gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/60">
                            <input id="select_all_downgrade_opt1" type="checkbox" data-list-key="downgrade_opt1"
                                class="path-select-all h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900">
                            <label for="select_all_downgrade_opt1" class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                Select All (<span id="visible-count-downgrade_opt1">0</span>)
                            </label>
                            <span id="selected-count-downgrade_opt1" class="ml-auto text-xs font-semibold text-brand-600 dark:text-brand-400">0 selected</span>
                        </div>

                        <div id="list-downgrade_opt1" class="path-equipment-list max-h-56 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800" data-list-key="downgrade_opt1">
                            @foreach ($equipmentItems as $item)
                                <label class="path-equipment-option hidden cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/40"
                                    data-list-key="downgrade_opt1" data-category-id="{{ $item['category_id'] }}" data-equipment-id="{{ $item['id'] }}">
                                    <input type="checkbox" name="downgrade_path_option_1[]" value="{{ $item['id'] }}"
                                        class="path-equipment-checkbox h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900"
                                        data-list-key="downgrade_opt1" data-equipment-id="{{ $item['id'] }}"
                                        @checked(in_array($item['id'], $selectedDowngradeOption1, true))>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item['equipment_code'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div id="empty-downgrade_opt1" class="hidden px-4 py-6 text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">No equipment found for the selected category.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-4">Notes</h4>
                <label for="assignment_notes" class="sr-only">Assignment Notes</label>
                <textarea name="assignment_notes"
                    id="assignment_notes"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white @error('assignment_notes') border-red-500 @enderror"
                    rows="4"
                    placeholder="Add any notes about this assignment...">{{ old('assignment_notes', $assignment->assignment_notes ?? '') }}</textarea>
                @error('assignment_notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-4">Status</h4>
                <label for="is_active" class="inline-flex items-center gap-3 cursor-pointer">
                    <input class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900"
                        type="checkbox"
                        name="is_active"
                        id="is_active"
                        value="1"
                        @checked((bool) old('is_active', !isset($assignment) || $assignment->is_active))>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                </label>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productCategorySelect = document.getElementById('selected_category_id');
        const productSelect = document.getElementById('product_id');
        const equipmentCategorySelect = document.getElementById('equipment_category_filter');
        const equipmentList = document.getElementById('primary-equipment-list');
        const equipmentEmptyState = document.getElementById('primary-equipment-empty');
        const equipmentOptions = Array.from(document.querySelectorAll('.equipment-option'));
        const equipmentCheckboxes = Array.from(document.querySelectorAll('.equipment-checkbox'));
        const selectAllEquipment = document.getElementById('select_all_equipment');
        const selectedCount = document.getElementById('selected-equipment-count');
        const visibleCount = document.getElementById('visible-equipment-count');
        const productOptions = Array.from(productSelect.options).filter(option => option.value !== '');

        const pathListKeys = ['upgrade_primary', 'upgrade_alt1', 'upgrade_alt2', 'downgrade_opt1'];
        const pathCategorySelects = Array.from(document.querySelectorAll('.path-category-select'));
        const pathSelectAllCheckboxes = Array.from(document.querySelectorAll('.path-select-all'));
        const pathCheckboxes = Array.from(document.querySelectorAll('.path-equipment-checkbox'));
        const pathOptions = Array.from(document.querySelectorAll('.path-equipment-option'));

        function getProductCategoryIds(option) {
            if (!option || !option.dataset.categoryIds) {
                return [];
            }

            try {
                return JSON.parse(option.dataset.categoryIds).map(Number);
            } catch (error) {
                return [];
            }
        }

        function syncSelectedCount() {
            const checkedCount = equipmentCheckboxes.filter(checkbox => checkbox.checked).length;
            selectedCount.textContent = `${checkedCount} selected`;
        }

        function syncPathSelectedCount(listKey) {
            const countElement = document.getElementById(`selected-count-${listKey}`);
            if (!countElement) {
                return;
            }

            const checkedCount = pathCheckboxes.filter((checkbox) => checkbox.dataset.listKey === listKey && checkbox.checked).length;
            countElement.textContent = `${checkedCount} selected`;
        }

        function syncVisibleState() {
            const activeCategoryId = equipmentCategorySelect.value;
            let visibleItems = 0;
            let visibleCheckedItems = 0;

            equipmentOptions.forEach((option) => {
                const isVisible = activeCategoryId !== '' && option.dataset.categoryId === activeCategoryId;
                option.classList.toggle('hidden', !isVisible);
                option.classList.toggle('flex', isVisible);

                if (isVisible) {
                    visibleItems += 1;
                    const checkbox = option.querySelector('.equipment-checkbox');
                    if (checkbox.checked) {
                        visibleCheckedItems += 1;
                    }
                }
            });

            visibleCount.textContent = String(visibleItems);
            equipmentList.classList.toggle('hidden', visibleItems === 0);
            equipmentEmptyState.classList.toggle('hidden', visibleItems !== 0);
            selectAllEquipment.checked = visibleItems > 0 && visibleCheckedItems === visibleItems;
            selectAllEquipment.indeterminate = visibleCheckedItems > 0 && visibleCheckedItems < visibleItems;
        }

        function syncPathVisibility(listKey) {
            const categorySelect = document.querySelector(`.path-category-select[data-list-key="${listKey}"]`);
            const listElement = document.getElementById(`list-${listKey}`);
            const emptyElement = document.getElementById(`empty-${listKey}`);
            const visibleCountElement = document.getElementById(`visible-count-${listKey}`);
            const selectAllElement = document.querySelector(`.path-select-all[data-list-key="${listKey}"]`);
            const activeCategoryId = categorySelect ? categorySelect.value : '';

            if (!listElement || !emptyElement || !visibleCountElement || !selectAllElement) {
                return;
            }

            let visibleItems = 0;
            let visibleCheckedItems = 0;

            pathOptions.forEach((option) => {
                if (option.dataset.listKey !== listKey) {
                    return;
                }

                const isVisible = activeCategoryId !== '' && option.dataset.categoryId === activeCategoryId;
                option.classList.toggle('hidden', !isVisible);
                option.classList.toggle('flex', isVisible);

                if (isVisible) {
                    visibleItems += 1;
                    const checkbox = option.querySelector('.path-equipment-checkbox');
                    if (checkbox && checkbox.checked) {
                        visibleCheckedItems += 1;
                    }
                }
            });

            visibleCountElement.textContent = String(visibleItems);
            listElement.classList.toggle('hidden', visibleItems === 0);
            emptyElement.classList.toggle('hidden', visibleItems !== 0);
            selectAllElement.checked = visibleItems > 0 && visibleCheckedItems === visibleItems;
            selectAllElement.indeterminate = visibleCheckedItems > 0 && visibleCheckedItems < visibleItems;

            syncPathSelectedCount(listKey);
        }

        function syncAllPathVisibility() {
            pathListKeys.forEach((listKey) => syncPathVisibility(listKey));
        }

        function clearPrimaryEquipmentSelection() {
            equipmentCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            syncSelectedCount();
        }

        function clearPathEquipmentSelection(listKey) {
            pathCheckboxes.forEach((checkbox) => {
                if (checkbox.dataset.listKey === listKey) {
                    checkbox.checked = false;
                }
            });
            syncPathSelectedCount(listKey);
        }

        function clearCategorySelections(syncPathCategories = true) {
            clearPrimaryEquipmentSelection();

            if (syncPathCategories) {
                pathListKeys.forEach((listKey) => clearPathEquipmentSelection(listKey));
            }
        }

        function syncCategoryFilters(categoryId, syncPathCategories = true, clearSelections = false) {
            equipmentCategorySelect.value = categoryId;

            if (syncPathCategories) {
                pathCategorySelects.forEach((selectElement) => {
                    selectElement.value = categoryId;
                });
            }

            if (clearSelections) {
                clearCategorySelections(syncPathCategories);
            }

            syncVisibleState();
            syncAllPathVisibility();
        }

        function enforceUniqueEquipmentSelection() {
            const selectedByList = {};
            const selectedGlobal = new Set();

            selectedByList.primary = new Set(
                equipmentCheckboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value)
            );

            pathListKeys.forEach((listKey) => {
                selectedByList[listKey] = new Set(
                    pathCheckboxes
                        .filter((checkbox) => checkbox.dataset.listKey === listKey && checkbox.checked)
                        .map((checkbox) => checkbox.value)
                );
            });

            Object.values(selectedByList).forEach((set) => {
                set.forEach((value) => selectedGlobal.add(value));
            });

            function shouldDisable(equipmentId, currentListKey) {
                for (const [listKey, set] of Object.entries(selectedByList)) {
                    if (listKey !== currentListKey && set.has(equipmentId)) {
                        return true;
                    }
                }
                return false;
            }

            equipmentCheckboxes.forEach((checkbox) => {
                const disable = shouldDisable(checkbox.value, 'primary');
                if (disable && checkbox.checked) {
                    checkbox.checked = false;
                    selectedByList.primary.delete(checkbox.value);
                }
                checkbox.disabled = disable;
            });

            pathCheckboxes.forEach((checkbox) => {
                const currentListKey = checkbox.dataset.listKey;
                const disable = shouldDisable(checkbox.value, currentListKey);
                if (disable && checkbox.checked) {
                    checkbox.checked = false;
                    selectedByList[currentListKey].delete(checkbox.value);
                }
                checkbox.disabled = disable;
            });

            syncSelectedCount();
            syncVisibleState();
            syncAllPathVisibility();
        }

        function syncProductsForCategory(preserveSelection = true) {
            const selectedCategory = productCategorySelect.value;
            const currentProduct = preserveSelection ? productSelect.value : '';
            let hasVisibleSelectedProduct = false;

            productOptions.forEach((option) => {
                const categoryIds = getProductCategoryIds(option);
                const matches = selectedCategory === '' || categoryIds.includes(Number(selectedCategory));
                option.hidden = !matches;

                if (matches && option.value === currentProduct) {
                    hasVisibleSelectedProduct = true;
                }
            });

            if (selectedCategory === '') {
                productSelect.value = hasVisibleSelectedProduct ? currentProduct : '';
                return;
            }

            if (!hasVisibleSelectedProduct) {
                const firstVisibleOption = productOptions.find((option) => !option.hidden);
                productSelect.value = firstVisibleOption ? firstVisibleOption.value : '';
            }
        }

        function syncCategoryFromProduct(syncPathCategories = false, clearSelections = false) {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const categoryIds = getProductCategoryIds(selectedOption);
            const currentCategoryId = productCategorySelect.value;
            const nextCategoryId = categoryIds[0] ? String(categoryIds[0]) : '';
            const shouldClearSelections = clearSelections && nextCategoryId !== currentCategoryId;

            if (nextCategoryId !== productCategorySelect.value) {
                productCategorySelect.value = nextCategoryId;
            }

            syncProductsForCategory(true);
            syncCategoryFilters(nextCategoryId, syncPathCategories, shouldClearSelections);
        }

        productCategorySelect.addEventListener('change', function() {
            syncProductsForCategory(false);
            syncCategoryFilters(productCategorySelect.value, true, true);
            enforceUniqueEquipmentSelection();
        });

        productSelect.addEventListener('change', function() {
            syncCategoryFromProduct(true, true);
            enforceUniqueEquipmentSelection();
        });

        equipmentCategorySelect.addEventListener('change', function() {
            if (equipmentCategorySelect.value !== productCategorySelect.value) {
                productCategorySelect.value = equipmentCategorySelect.value;
                syncProductsForCategory(false);
            }

            syncCategoryFilters(equipmentCategorySelect.value, true, true);
            enforceUniqueEquipmentSelection();
        });

        equipmentCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', function() {
                syncSelectedCount();
                syncVisibleState();
                enforceUniqueEquipmentSelection();
            });
        });

        selectAllEquipment.addEventListener('change', function() {
            const activeCategoryId = equipmentCategorySelect.value;
            equipmentOptions.forEach((option) => {
                if (option.dataset.categoryId === activeCategoryId) {
                    const checkbox = option.querySelector('.equipment-checkbox');
                    if (checkbox && !checkbox.disabled) {
                        checkbox.checked = selectAllEquipment.checked;
                    }
                }
            });

            syncSelectedCount();
            syncVisibleState();
            enforceUniqueEquipmentSelection();
        });

        pathCategorySelects.forEach((selectElement) => {
            selectElement.addEventListener('change', function() {
                clearPathEquipmentSelection(selectElement.dataset.listKey);
                syncPathVisibility(selectElement.dataset.listKey);
                enforceUniqueEquipmentSelection();
            });
        });

        pathCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', function() {
                syncPathSelectedCount(checkbox.dataset.listKey);
                syncPathVisibility(checkbox.dataset.listKey);
                enforceUniqueEquipmentSelection();
            });
        });

        pathSelectAllCheckboxes.forEach((selectAllElement) => {
            selectAllElement.addEventListener('change', function() {
                const listKey = selectAllElement.dataset.listKey;
                const categorySelect = document.querySelector(`.path-category-select[data-list-key="${listKey}"]`);
                const activeCategoryId = categorySelect ? categorySelect.value : '';

                pathOptions.forEach((option) => {
                    if (option.dataset.listKey !== listKey || option.dataset.categoryId !== activeCategoryId) {
                        return;
                    }

                    const checkbox = option.querySelector('.path-equipment-checkbox');
                    if (checkbox && !checkbox.disabled) {
                        checkbox.checked = selectAllElement.checked;
                    }
                });

                syncPathSelectedCount(listKey);
                syncPathVisibility(listKey);
                enforceUniqueEquipmentSelection();
            });
        });

        syncProductsForCategory(true);

        if (productSelect.value) {
            syncCategoryFromProduct();
        } else {
            equipmentCategorySelect.value = productCategorySelect.value;
            syncVisibleState();
        }

        syncSelectedCount();
        pathListKeys.forEach((listKey) => {
            const select = document.querySelector(`.path-category-select[data-list-key="${listKey}"]`);
            if (select && !select.value) {
                select.value = equipmentCategorySelect.value;
            }
        });
        syncAllPathVisibility();
        enforceUniqueEquipmentSelection();
    });
</script>

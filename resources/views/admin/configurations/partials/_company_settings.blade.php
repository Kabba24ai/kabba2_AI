{{-- Company Settings — identity used system-wide (documents, branding) --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-building-office class="h-5 w-5 text-green-600" />
        <h3 class="text-lg font-bold text-gray-900">Company Identity</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1 required">
                Company Display Name
            </label>
            {!! html()->input('text', 'company_name', old('company_name', $settings['Company Settings']['company_name']['setting_value'] ?? ''))->class([
                    'w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('company_name'),
                    'border-red-500' => $errors->has('company_name'),
                ])->attributes([
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Company Settings']['company_name']['placeholder'] ?? '',
                    'required' => true,
                    'id' => 'company_name',
                ]) !!}
            @error('company_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="main_url" class="block text-sm font-medium text-gray-700 mb-1 required">
                Main URL / Website
            </label>
            {!! html()->input('text', 'main_url', old('main_url', $settings['Company Settings']['main_url']['setting_value'] ?? ''))->class([
                    'w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('main_url'),
                    'border-red-500' => $errors->has('main_url'),
                ])->attributes([
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Company Settings']['main_url']['placeholder'] ?? '',
                    'required' => true,
                    'id' => 'main_url',
                ]) !!}
            @error('main_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="company_main_phone" class="block text-sm font-medium text-gray-700 mb-1 required">
                Main Phone Number
            </label>
            {!! html()->input('text', 'company_main_phone', old('company_main_phone', $settings['Company Settings']['company_main_phone']['setting_value'] ?? ''))->class([
                    'masked-phone w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('company_main_phone'),
                    'border-red-500' => $errors->has('company_main_phone'),
                ])->attributes([
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Company Settings']['company_main_phone']['placeholder'] ?? '',
                    'required' => true,
                    'id' => 'company_main_phone',
                ]) !!}
            @error('company_main_phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="company_sales_phone" class="block text-sm font-medium text-gray-700 mb-1">
                Sales Phone Number <span class="text-xs text-gray-400">(optional)</span>
            </label>
            {!! html()->input('text', 'company_sales_phone', old('company_sales_phone', $settings['Company Settings']['company_sales_phone']['setting_value'] ?? ''))->class([
                    'masked-phone w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('company_sales_phone'),
                    'border-red-500' => $errors->has('company_sales_phone'),
                ])->attributes([
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Company Settings']['company_sales_phone']['placeholder'] ?? '',
                    'id' => 'company_sales_phone',
                ]) !!}
            @error('company_sales_phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="store_hours_fallback" class="block text-sm font-medium text-gray-700 mb-1">
                Store Hours (fallback text)
            </label>
            {!! html()->textarea('store_hours_fallback', old('store_hours_fallback', $settings['Company Settings']['store_hours_fallback']['setting_value'] ?? ''))->class([
                    'w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('store_hours_fallback'),
                    'border-red-500' => $errors->has('store_hours_fallback'),
                ])->attributes([
                    'rows' => 3,
                    'placeholder' => $settings['Company Settings']['store_hours_fallback']['placeholder'] ?? '',
                    'id' => 'store_hours_fallback',
                ]) !!}
            <p class="mt-1 text-xs text-gray-400">
                Documents use each store's structured Hours of Operation (Store Settings) when available.
                This text is only used for stores that have no hours configured. One line per range.
            </p>
            @error('store_hours_fallback')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

{{-- Company Settings --}}

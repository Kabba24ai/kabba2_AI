{{-- Terms & Conditions Settings --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">

    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-document-text class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Terms & Conditions</h3>
    </div>

    <div class="space-y-6">

        {{-- Info Notice --}}
        <div class="rounded-md p-4 text-left bg-blue-50 border border-blue-200 text-blue-900">
            <div class="flex items-start space-x-3">
                <x-heroicon-o-information-circle class="w-8 h-8 text-blue-600" />
                <div>
                    <h4 class="text-sm font-medium text-blue-800">Info</h4>
                    <p class="text-sm mt-1 text-blue-700">
                        Manage the Terms & Conditions shown on your website.
                    </p>
                </div>
            </div>
        </div>

        {{-- Title --}}
        <div class="space-y-1.5">
            <label for="terms_title" class="block text-sm font-medium text-gray-700 text-left">
                Title
            </label>

            {!! html()->text('terms[terms_conditions_title]',
            $settings['Terms & Conditions Settings']['terms_conditions_title']['setting_value'] ?? ''
            )->class('w-full rounded border border-gray-300 px-3 py-2 text-sm
            focus:ring-2 focus:ring-blue-500 focus:border-blue-500')
            ->id('terms_conditions_title')
            ->placeholder('Enter Title') !!}


            @error('terms.terms_conditions_title')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Status --}}
        <div class="space-y-1.5 hidden">
            <label class="block text-sm font-medium text-gray-700 text-left">
                Status
            </label>

            {!! html()->select(
            'terms[terms_conditions_status]',
            ['Published' => 'Published', 'Draft' => 'Draft', 'Pending' => 'Pending'],
            $settings['Terms & Conditions Settings']['terms_conditions_status']['setting_value'] ?? 'Published'
            )->class('w-full rounded border border-gray-300 px-3 py-2 text-sm
            focus:ring-2 focus:ring-blue-500 focus:border-blue-500')
            ->placeholder('Please Select') !!}


            @error('terms.status')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Description --}}
        <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 text-left">
                Description
            </label>

            {!! html()->textarea(
            'terms[terms_conditions_description]',
            $settings['Terms & Conditions Settings']['terms_conditions_description']['setting_value'] ?? ''
            )->class('tinymce w-full min-h-[300px] rounded border border-gray-300 px-4 py-2 text-sm
            focus:ring-2 focus:ring-blue-500 focus:border-blue-500')
            ->id('terms_conditions_description')
            ->placeholder('Enter Description') !!}


            @error('terms.description')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

    </div>
</div>
{{-- End Terms --}}
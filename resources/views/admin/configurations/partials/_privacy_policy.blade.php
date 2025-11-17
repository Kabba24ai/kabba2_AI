{{-- Privacy Policy Settings --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">

    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-lock-closed class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Privacy Policy</h3>
    </div>

    <div class="space-y-6">

        {{-- Info Notice --}}
        <div class="rounded-md p-4 text-left bg-blue-50 border border-blue-200 text-blue-900">
            <div class="flex items-start space-x-3">
                <x-heroicon-o-information-circle class="w-8 h-8 text-blue-600" />
                <div>
                    <h4 class="text-sm font-medium text-blue-800">Info</h4>
                    <p class="text-sm mt-1 text-blue-700">
                        Manage the Privacy Policy content shown on your website.
                    </p>
                </div>
            </div>
        </div>

        {{-- Title --}}
        <div class="space-y-1.5">
            <label for="privacy_title" class="block text-sm font-medium text-gray-700 text-left">
                Title
            </label>

            {!! html()->text('privacy[privacy_policy_title]',
            $settings['Privacy Policy Settings']['privacy_policy_title']['setting_value'] ?? ''
            )->class('w-full rounded border border-gray-300 px-3 py-2 text-sm
            focus:ring-2 focus:ring-blue-500 focus:border-blue-500')
            ->id('privacy_policy_title')
            ->placeholder('Enter Title') !!}


            @error('privacy.title')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Status --}}
        <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 text-left">
                Status
            </label>

            {!! html()->select(
            'privacy[privacy_policy_status]',
            ['Published' => 'Published', 'Draft' => 'Draft', 'Pending' => 'Pending'],
            $settings['Privacy Policy Settings']['privacy_policy_status']['setting_value'] ?? 'Published'
            )->class('w-full rounded border border-gray-300 px-3 py-2 text-sm
            focus:ring-2 focus:ring-blue-500 focus:border-blue-500')
            ->placeholder('Please Select') !!}


            @error('privacy.status')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Description --}}
        <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 text-left">
                Description
            </label>

            {!! html()->textarea(
            'privacy[privacy_policy_description]',
            $settings['Privacy Policy Settings']['privacy_policy_description']['setting_value'] ?? ''
            )->class('tinymce w-full min-h-[300px] rounded border border-gray-300 px-4 py-2 text-sm
            focus:ring-2 focus:ring-blue-500 focus:border-blue-500')
            ->id('privacy_policy_description')
            ->placeholder('Enter Description') !!}


            @error('privacy.description')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

    </div>
</div>
{{-- End Privacy --}}
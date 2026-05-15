@extends('admin.layouts.app', ['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'Contact Us Section')

@section('content')
    <div class="bg-gray-50 flex flex-col">
        <div class="flex-1 overflow-auto">
            {{-- Header --}}
            <div class=" border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- <x-heroicon-o-cog-8-tooth class="h-10 w-10" /> -->
                        <div>
                            <svg class="w-9 h-9" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                aria-hidden="true" data-slot="icon">
                                <path fill-rule="evenodd"
                                    d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 0 1-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 0 0 6.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 0 1 1.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="h-6 border-l border-gray-300"></div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Contact Us Section</h1>
                            <!-- <p class="text-sm text-gray-600">Manage system settings and configurations</p> -->
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            {{-- Notice (now half width) --}}
            <div class="mt-4 rounded-md p-4 text-left bg-blue-50 border border-blue-200 text-blue-900">

                <div class="flex items-start space-x-3">
                    <x-heroicon-o-information-circle class="w-8 h-8 text-blue-600" />
                    <div>
                        Info
                        <p class="text-sm mt-1 text-blue-700">
                            You can manage and update the Contact Us shown on your website from the Contact Us page
                            <a href="{{ route('admin.stores.index') }}"
                                class="font-medium text-blue-800 underline hover:text-blue-900">
                                Click here to go to Contact Us settings →
                            </a>
                        </p>
                    </div>
                </div>
            </div>

        </div>

        {{-- FORM SECTION --}}
        <div class="px-6 mt-6">
            <form method="POST" action="{{ route('admin.website-management.contact-us.update') }}" data-parsley-validate>
                @csrf
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center space-x-2 mb-6">
                        <x-heroicon-o-pencil-square class="h-5 w-5 text-blue-600" />
                        <h3 class="text-lg font-bold text-gray-900">
                            Contact Us Content
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 gap-6">
                        {{-- Title --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Title
                            </label>
                            {!! html()->text('contact_title', old('contact_title', $settings['contact_title'] ?? ''))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')->attributes([
                                    'placeholder' => 'Example: Speak with a human – No frustrating menus and bots',
                                ]) !!}
                        </div>
                        {{-- Subtitle --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Subtitle
                            </label>
                            {!! html()->textarea('contact_subtitle', old('contact_subtitle', $settings['contact_subtitle'] ?? ''))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')->attributes([
                                    'rows' => 3,
                                    'placeholder' => 'Example: We might be on the phone with others ...',
                                ]) !!}
                        </div>

                        {{-- SEO Meta Section --}}
                        <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-medium text-sm text-gray-700">Search Engine Optimize</span>
                                <a href="#"
                                    onclick="document.getElementById('contact-seo-fields').classList.toggle('hidden'); return false;"
                                    class="text-sm text-blue-600 hover:underline">Edit SEO meta</a>
                            </div>

                            <div class="text-sm text-gray-800">
                                <p class="text-blue-600 font-semibold truncate">
                                    {{ old('contact_seo_title', $settings['contact_seo_title'] ?? '') }}
                                </p>
                                <p class="text-green-700 text-xs truncate">
                                    <a href="{{ route('front.contact-us.index') }}"
                                        class="text-blue-600 hover:underline break-all" target="_blank">
                                        {{ route('front.contact-us.index') }}
                                    </a>
                                </p>
                                <p class="text-gray-700 mt-1">
                                    {{ old('contact_seo_description', $settings['contact_seo_description'] ?? '') }}
                                </p>
                            </div>

                            <div id="contact-seo-fields" class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        SEO Title
                                    </label>
                                    {!! html()->text('contact_seo_title', old('contact_seo_title', $settings['contact_seo_title'] ?? ''))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')->attributes([
                                            'placeholder' => 'SEO Title',
                                            'maxlength' => 60,
                                            'data-parsley-maxlength' => 60,
                                        ]) !!}
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        SEO Description
                                    </label>
                                    {!! html()->textarea('contact_seo_description', old('contact_seo_description', $settings['contact_seo_description'] ?? ''))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')->attributes([
                                            'rows' => 3,
                                            'placeholder' => 'SEO Description',
                                            'maxlength' => 160,
                                            'data-parsley-maxlength' => 160,
                                        ]) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Save Button --}}
                    <div class="mt-6 text-right">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium shadow-sm">
                            Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>



    </div>
@endsection

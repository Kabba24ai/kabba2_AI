@php
    // Valid tabs on THIS page — a remembered tab from another admin screen
    // must never leave every panel hidden.
    $footerTabs = ['footer-content', 'feature-strip', 'terms-policy', 'privacy-policy', 'social-media', 'contact', 'faq'];
@endphp
<div class="rounded-xl dark:border-gray-800"
    x-data="{
        activeTab: (() => {
            const saved = localStorage.getItem('footer_global_active_tab');
            return @js($footerTabs).includes(saved) ? saved : 'footer-content';
        })(),
        init() {
            this.$watch('activeTab', (value) => {
                localStorage.setItem('footer_global_active_tab', value);
            });
        }
}">
    <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex space-x-2 overflow-x-auto
            [&::-webkit-scrollbar-thumb]:rounded-full
            [&::-webkit-scrollbar-thumb]:bg-gray-200
            dark:[&::-webkit-scrollbar-thumb]:bg-gray-600
            dark:[&::-webkit-scrollbar-track]:bg-transparent
            [&::-webkit-scrollbar]:h-1.5">

            <!-- Footer Content (global) -->
            <button id="tab-footer-content"
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors"
                x-bind:class="activeTab === 'footer-content'
                    ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400'
                    : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'footer-content'">
                Footer Content
            </button>

            <!-- Feature Strip (global) -->
            <button id="tab-feature-strip"
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors"
                x-bind:class="activeTab === 'feature-strip'
                    ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400'
                    : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'feature-strip'">
                Feature Strip
            </button>

            <!-- Terms & Conditions -->
            <button id="tab-terms-policy"
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors"
                x-bind:class="activeTab === 'terms-policy'
                    ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400'
                    : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'terms-policy'">
                Terms & Conditions
            </button>

            <!-- Privacy Policy -->
            <button id="tab-privacy-policy"
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors"
                x-bind:class="activeTab === 'privacy-policy'
                    ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400'
                    : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'privacy-policy'">
                Privacy Policy
            </button>

            <!-- Social Media -->
            <button id="tab-social-media"
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors"
                x-bind:class="activeTab === 'social-media'
                    ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400'
                    : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'social-media'">
                Social Media Settings
            </button>

            <!-- Contact Us -->
            <button id="tab-contact"
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors"
                x-bind:class="activeTab === 'contact'
                    ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400'
                    : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'contact'">
                Contact Us Settings
            </button>

            <!-- FAQ -->
            <button id="tab-faq"
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors"
                x-bind:class="activeTab === 'faq'
                    ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400'
                    : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'faq'">
                FAQ
            </button>

        </nav>
    </div>

    <div class="dark:border-gray-800 my-2">

        @php
            $builderContext = [
                'sections'   => $sections   ?? collect(),
                'itemsByKey' => $itemsByKey ?? collect(),
            ];
        @endphp

        <!-- Footer Content (global — stored on the home website page, rendered on every public page) -->
        <div x-show="activeTab === 'footer-content'" x-cloak>
            @if(($page ?? null) && isset($components['footer']))
                <div class="mb-4 flex items-start gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2">
                    <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 shrink-0 mt-0.5" />
                    <p class="text-sm text-blue-800">
                        This footer appears on <strong>every public page</strong> of the website. Changes go live as soon as they are saved.
                    </p>
                </div>
                @include($components['footer']->adminView(), $components['footer']->viewData($builderContext))
            @else
                <div class="flex items-center gap-2 bg-yellow-50 border border-yellow-200 rounded-md px-4 py-3 text-sm text-yellow-800">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0"/>
                    No website page data found. Run: <code class="font-mono bg-yellow-100 px-1 rounded">php artisan db:seed --class="Database\Seeders\WebsiteManagement\HomePageBuilderSeeder"</code>
                </div>
            @endif
        </div>

        <!-- Feature Strip (global — the benefits bar shown directly above the footer) -->
        <div x-show="activeTab === 'feature-strip'" x-cloak>
            @if(($page ?? null) && isset($components['feature_strip']))
                <div class="mb-4 flex items-start gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2">
                    <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 shrink-0 mt-0.5" />
                    <p class="text-sm text-blue-800">
                        The feature strip appears directly above the footer on <strong>every public page</strong>. Changes go live as soon as they are saved.
                    </p>
                </div>
                @include($components['feature_strip']->adminView(), $components['feature_strip']->viewData($builderContext))
            @else
                <div class="flex items-center gap-2 bg-yellow-50 border border-yellow-200 rounded-md px-4 py-3 text-sm text-yellow-800">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0"/>
                    No website page data found. Run: <code class="font-mono bg-yellow-100 px-1 rounded">php artisan db:seed --class="Database\Seeders\WebsiteManagement\HomePageBuilderSeeder"</code>
                </div>
            @endif
        </div>

        <!-- Terms & Policy -->
        <div x-show="activeTab === 'terms-policy'">

            @include('admin.website_management.footer.partials._terms_policy')

        </div>

        <!-- Contact -->
        <div x-show="activeTab === 'contact'">

            @include('admin.website_management.footer.partials._contact_us_settings')

        </div>

        <!-- Social Media -->
        <div x-show="activeTab === 'social-media'">
            <x-admin.configurations.config-form
                id="config-social-media-form"
                :action="route('admin.configurations.save-social-media-settings')"
                saveLabel="Save">
                @include('admin.website_management.footer.partials._social_media_settings')
            </x-admin.configurations.config-form>
        </div>

        <!-- FAQ -->
        <div x-show="activeTab === 'faq'">

            @include('admin.website_management.footer.partials._faq_settings')

        </div>

        <!-- Privacy Policy -->
        <div x-show="activeTab === 'privacy-policy'">
            <x-admin.configurations.config-form
                id="config-privacy-form"
                :action="route('admin.configurations.save-privacy-policy')"
                saveLabel="Save">
                @include('admin.website_management.footer.partials._privacy_policy')
            </x-admin.configurations.config-form>
        </div>

    </div>
</div>


@push('js')




@endpush
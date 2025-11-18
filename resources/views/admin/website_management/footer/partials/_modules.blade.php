<div class="rounded-xl dark:border-gray-800"
    x-data="{
        activeTab: localStorage.getItem('admin_config_active_tab') || 'terms-policy',
        init() {
            this.$watch('activeTab', (value) => {
                localStorage.setItem('admin_config_active_tab', value);
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
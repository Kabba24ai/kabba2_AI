<div class="rounded-xl dark:border-gray-800" x-data="{
    activeTab: localStorage.getItem('admin_config_active_tab') || 'product-settings',
    init() {
        this.$watch('activeTab', (value) => {
            localStorage.setItem('admin_config_active_tab', value);
        });
    }
}">
    <div class="border-b border-gray-200 dark:border-gray-800">
        <nav
            class="-mb-px flex space-x-2 overflow-x-auto [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 dark:[&::-webkit-scrollbar-thumb]:bg-gray-600 dark:[&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar]:h-1.5">
            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'product-settings' ?
                    ' text-brand-500 border-brand-500  dark:text-brand-400 dark:border-brand-400' :
                    'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'product-settings'" id="tab-product-settings">
                Product Settings
            </button>

            <!-- <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'contact-us-settings' ?
                    ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' :
                    'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'contact-us-settings'" id="tab-contact-us-settings">
                Contact Us Settings
            </button> -->

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'communication-settings' ?
                    ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' :
                    'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'communication-settings'" id="tab-communication-settings">
                Communication Settings
            </button>

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'admin-settings' ?
                    ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' :
                    'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'admin-settings'" id="tab-admin-settings">
                Admin Settings
            </button>

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'mail-send-settings' ?
                    ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' :
                    'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'mail-send-settings'" id="tab-mail-send-settings">
                Mail Send Settings
            </button>

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'payment-integration' ?
                    ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' :
                    'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'payment-integration'" id="tab-payment-integration">
                Payment Integration
            </button>

            <!-- <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'social-media-settings' ?
                    ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' :
                    'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'social-media-settings'" id="tab-social-media-settings">
                Social Media Settings
            </button> -->

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'invoice-settings' ?
                    ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' :
                    'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'invoice-settings'" id="tab-invoice-settings">
                Invoice Settings
            </button>

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'price-settings' ?
                    ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' :
                    'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'price-settings'" id="tab-price-settings">
                Price Settings
            </button>

            <!-- FIXED -->
            <!-- <button
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'terms-and-conditions' ?
            'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400' :
            'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'terms-and-conditions'"
                id="tab-terms-and-conditions">
                Terms and Conditions
            </button> -->

            <!-- FIXED -->
            <!-- <button
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'privacy-policy' ?
            'text-brand-500 border-brand-500 dark:border-brand-400 dark:text-brand-400' :
            'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'privacy-policy'"
                id="tab-privacy-policy">
               Terms and Privacy Policy
            </button> -->

        </nav>
    </div>

    <div class="dark:border-gray-800 my-2">
        <div x-show="activeTab === 'product-settings'">
            <x-admin.configurations.config-form id="config-product-rate-form" :action="route('admin.configurations.save-product-settings')" saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._product_settings')
                </div>
            </x-admin.configurations.config-form>
        </div>

        <div x-show="activeTab === 'contact-us-settings'">
            <x-admin.configurations.config-form id="config-contact-form" :action="route('admin.configurations.save-contact-us-settings')" saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._contact_us_settings')
                </div>
            </x-admin.configurations.config-form>
        </div>

        <div x-show="activeTab === 'communication-settings'">
            <x-admin.configurations.config-form id="config-communication-form" :action="route('admin.configurations.save-communication-settings')" saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._communication_settings')
                </div>
                <!-- Notification Settings -->
                    @include('admin.configurations.partials._notification_settings')
                <!-- Notification Settings -->

            </x-admin.configurations.config-form>
        </div>

        <div x-show="activeTab === 'admin-settings'">
            <x-admin.configurations.config-form id="config-admin-form" :action="route('admin.configurations.save-admin-settings')" saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._admin_settings')
                </div>
            </x-admin.configurations.config-form>
        </div>

        <div x-show="activeTab === 'mail-send-settings'">
            <x-admin.configurations.config-form id="config-mail-send-form" :action="route('admin.configurations.save-mail-send-settings')" saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._mail_send_settings')
                </div>
            </x-admin.configurations.config-form>
        </div>

        <div x-show="activeTab === 'payment-integration'">
            <x-admin.configurations.config-form id="config-payment-integration-form" :action="route('admin.configurations.save-payment-integration-settings')" saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._payment_integration_settings')
                </div>
            </x-admin.configurations.config-form>
        </div>

        <div x-show="activeTab === 'social-media-settings'">
            <x-admin.configurations.config-form id="config-social-media-form" :action="route('admin.configurations.save-social-media-settings')" saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._social_media_settings')
                </div>
            </x-admin.configurations.config-form>
        </div>

        <div x-show="activeTab === 'invoice-settings'">
            <x-admin.configurations.config-form id="config-invoice-form" :action="route('admin.configurations.save-invoice-settings')" saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._invoice_settings')
                </div>
            </x-admin.configurations.config-form>
        </div>

        <div x-show="activeTab === 'price-settings'">
            <x-admin.configurations.config-form id="config-price-form" :action="route('admin.configurations.save-price-settings')" saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._price_settings')
                </div>
            </x-admin.configurations.config-form>
        </div>
        <!-- <div x-show="activeTab === 'terms-and-conditions'">
            <x-admin.configurations.config-form
                id="config-terms-form"
                :action="route('admin.configurations.save-terms-and-conditions')"
                saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._terms_and_conditions')
                </div>
            </x-admin.configurations.config-form>
        </div> -->

        <div x-show="activeTab === 'privacy-policy'">
            <x-admin.configurations.config-form
                id="config-privacy-form"
                :action="route('admin.configurations.save-privacy-policy')"
                saveLabel="Save">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @include('admin.configurations.partials._privacy_policy')
                </div>
            </x-admin.configurations.config-form>
        </div>

    </div>
</div>

{{-- Modal (hidden by default) --}}
<div id="verify-modal" class="fixed inset-0 z-[99999] hidden" role="dialog" aria-modal="true"
    aria-labelledby="verify-title">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40"></div>

    <!-- Panel -->
    <div class="relative mx-auto my-10 max-w-lg w-[92%]">
        <div class="bg-white rounded-xl shadow-xl border border-gray-200">
            <div class="px-5 pt-4 pb-2 flex items-start justify-between">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-lock-closed class="w-5 h-5 text-red-500" />
                    <h3 id="verify-title" class="text-lg font-semibold text-gray-900">Security Verification Required
                    </h3>
                </div>
                <button type="button" class="p-1 text-gray-400 hover:text-gray-600" data-modal-close
                    aria-label="Close">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            <div class="px-5 pb-4">
                <p class="text-sm text-gray-600 mb-3">Enter your Master Password to edit the Master Passcode.</p>

                <div class="relative">
                    <input id="verify-password" type="password" autocomplete="current-password"
                        class="w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 border-gray-300"
                        placeholder="Enter your Master Password">
                    <!-- eye -->
                    <button type="button"
                        class="absolute inset-y-0 right-0 w-10 grid place-items-center text-gray-400 hover:text-gray-600"
                        data-toggle="visibility" data-target="verify-password" aria-label="Show password">
                        <x-heroicon-o-eye data-eye class="w-5 h-5" />
                        <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                    </button>
                </div>

                <p id="verify-error" class="text-sm text-red-600 mt-2 hidden"></p>

                <div class="mt-4 flex items-center gap-3">
                    <button type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-indigo-500 text-white hover:bg-indigo-600 disabled:opacity-60"
                        id="verify-submit">
                        <x-heroicon-o-lock-closed class="w-4 h-4" />
                        Verify & Edit
                    </button>
                    <button type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                        data-modal-close>
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    (function() {
        // password visibility (delegated)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-toggle="visibility"]');
            if (!btn) return;
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            // Only allow visibility toggle if input is NOT disabled (i.e., verified)
            if (input.hasAttribute('disabled')) {
                // If not verified, open modal for verification
                if (input.id === 'verify-password') return; // Don't open modal for the modal's own input
                // Find related lock button and trigger modal
                const opener = document.querySelector(`[data-open-verify][data-field-id="${input.id}"]`);
                if (opener) opener.click();
                return;
            }

            const eye = btn.querySelector('[data-eye]');
            const eyeOff = btn.querySelector('[data-eye-off]');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            if (eye) eye.classList.toggle('hidden', !isPassword);
            if (eyeOff) eyeOff.classList.toggle('hidden', isPassword);
            btn.setAttribute('aria-label', isPassword ? 'Hide value' : 'Show value');
            btn.setAttribute('aria-pressed', String(isPassword));
        });

        const modal = document.getElementById('verify-modal');
        const verifyInput = document.getElementById('verify-password');
        const verifyError = document.getElementById('verify-error');
        const verifyBtn = document.getElementById('verify-submit');

        let targetFieldId = null;
        let verifyUrl = "{{ route('admin.configurations.verify-master') }}";

        // open modal from lock buttons
        document.addEventListener('click', function(e) {
            const opener = e.target.closest('[data-open-verify]');
            if (!opener) return;

            targetFieldId = opener.getAttribute('data-field-id');

            const field = document.getElementById(targetFieldId);
            if (field && !field.hasAttribute('disabled')) return;

            openModal();
        });

        // close modal (X or Cancel or backdrop)
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-modal-close]') || e.target.closest('[data-modal-close]')) {
                closeModal();
            }
            if (e.target === modal) { // click backdrop
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        function openModal() {
            verifyInput.value = '';
            verifyError.textContent = '';
            verifyError.classList.add('hidden');
            modal.classList.remove('hidden');
            setTimeout(() => verifyInput.focus(), 0);
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        // submit verification
        verifyBtn.addEventListener('click', submitVerify);
        verifyInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') submitVerify();
        });

        async function submitVerify() {
            if (!verifyUrl || !targetFieldId) return;

            verifyBtn.disabled = true;
            verifyError.classList.add('hidden');

            try {
                const tokenTag = document.querySelector('meta[name="csrf-token"]');
                const csrf = tokenTag ? tokenTag.getAttribute('content') : '';

                const res = await fetch(verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        password: verifyInput.value,
                        field_name: targetFieldId,
                    }),
                });

                const data = await res.json();

                if (data && data.success) {
                    // enable the targeted input and focus it
                    const field = document.getElementById(targetFieldId);
                    if (field) {
                        field.value = data.field_value || '';
                        field.removeAttribute('disabled');
                        field.focus();
                        // optional little highlight
                        field.classList.add('ring-2', 'ring-green-400');
                        setTimeout(() => field.classList.remove('ring-2', 'ring-green-400'), 800);
                    }
                    closeModal();
                } else {
                    verifyError.textContent = (data && data.message) ? data.message : 'Verification failed.';
                    verifyError.classList.remove('hidden');
                }
            } catch (err) {
                verifyError.textContent = 'Something went wrong. Please try again.';
                verifyError.classList.remove('hidden');
                console.error(err);
            } finally {
                verifyBtn.disabled = false;
            }
        }
    })();
</script>
@endpush

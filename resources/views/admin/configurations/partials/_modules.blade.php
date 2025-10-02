{{-- Configuration Tabs --}}
<div class="space-y-6">
    <!-- Tabs header -->
    <div class="w-full bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="flex flex-wrap sm:flex-nowrap p-4 gap-2" data-tab-group="config">
            <button type="button" onclick="showConfigTab('config-admin', this)" id="tab-config-admin"
                class="tab-button bg-green-100 text-green-800 px-4 py-2 text-sm font-medium rounded-md transition">
                Admin Settings
            </button>
            <button type="button" onclick="showConfigTab('config-allocated', this)" id="tab-config-allocated"
                class="tab-button text-gray-700 px-4 py-2 text-sm font-medium rounded-md hover:bg-gray-100 transition">
                Allocated Settings
            </button>
            <button type="button" onclick="showConfigTab('config-communication', this)" id="tab-config-communication"
                class="tab-button text-gray-700 px-4 py-2 text-sm font-medium rounded-md hover:bg-gray-100 transition">
                Communication Settings
            </button>
            <button type="button" onclick="showConfigTab('config-contact', this)" id="tab-config-contact"
                class="tab-button text-gray-700 px-4 py-2 text-sm font-medium rounded-md hover:bg-gray-100 transition">
                Contact Us Settings
            </button>
            <button type="button" onclick="showConfigTab('config-product-rate', this)" id="tab-config-product-rate"
                class="tab-button text-gray-700 px-4 py-2 text-sm font-medium rounded-md hover:bg-gray-100 transition">
                Product Rate Settings
            </button>
        </div>
    </div>

    <!-- Tabs content -->
    <div id="config-admin" class="tab-content" data-tab-group="config">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @include('admin.configurations.partials._admin_settings')
        </div>
    </div>

    <div id="config-allocated" class="tab-content hidden" data-tab-group="config">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @include('admin.configurations.partials._allocated_settings')
        </div>
    </div>

    <div id="config-communication" class="tab-content hidden" data-tab-group="config">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @include('admin.configurations.partials._communication_settings')
        </div>
    </div>

    <div id="config-contact" class="tab-content hidden" data-tab-group="config">
        <x-admin.configurations.config-form id="config-contact-form" :action="route('admin.configurations.new.save-contact-us-settings')" saveLabel="Save">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @include('admin.configurations.partials._contact_us_settings')
            </div>
        </x-admin.configurations.config-form>
    </div>

    <div id="config-product-rate" class="tab-content hidden" data-tab-group="config">
        <x-admin.configurations.config-form id="config-product-rate-form" :action="route('admin.configurations.new.save-product-rate')" saveLabel="Save">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @include('admin.configurations.partials._product_rate_settings')
            </div>
        </x-admin.configurations.config-form>
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
                        Verify &amp; Edit
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
            let verifyUrl = null;

            // open modal from lock buttons
            document.addEventListener('click', function(e) {
                const opener = e.target.closest('[data-open-verify]');
                if (!opener) return;

                targetFieldId = opener.getAttribute('data-field-id');
                verifyUrl = opener.getAttribute('data-verify-url');

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
                            password: verifyInput.value
                        }),
                    });

                    const data = await res.json();

                    if (data && data.ok) {
                        // enable the targeted input and focus it
                        const field = document.getElementById(targetFieldId);
                        if (field) {
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

        // Minimal tabs controller scoped for Config page
        function showConfigTab(tabId, clickedBtn) {
            const group = 'config';
            // hide all contents in this group
            document.querySelectorAll(`.tab-content[data-tab-group="${group}"]`).forEach(content => {
                content.classList.add('hidden');
            });
            // show selected content
            const active = document.getElementById(tabId);
            if (active) active.classList.remove('hidden');

            // reset all buttons in this group header
            const header = document.querySelector(`[data-tab-group="${group}"]`);
            if (header) {
                header.querySelectorAll('button').forEach(btn => {
                    btn.classList.remove('bg-green-100', 'text-green-800');
                    btn.classList.add('text-gray-700');
                });
            }
            // set active styles for clicked button
            if (clickedBtn) {
                clickedBtn.classList.add('bg-green-100', 'text-green-800');
                clickedBtn.classList.remove('text-gray-700');
                // persist selection
                try {
                    localStorage.setItem('config_active_tab', tabId);
                } catch (e) {}
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Restore last active tab if available
            let initial = 'config-admin';
            try {
                const saved = localStorage.getItem('config_active_tab');
                if (saved && document.getElementById(saved)) initial = saved;
            } catch (e) {}

            const btn = document.getElementById('tab-' + initial);
            if (btn) {
                showConfigTab(initial, btn);
            } else {
                // fallback to first button
                const first = document.querySelector('[data-tab-group="config"] button');
                if (first) showConfigTab('config-admin', first);
            }
        });
    </script>
@endpush

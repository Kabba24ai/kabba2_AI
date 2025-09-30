{{-- Admin Settings (Tailwind + Vanilla JS) --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-lock-closed class="h-5 w-5 text-blue-600" aria-hidden="true" />
        <h3 class="text-lg font-bold text-gray-900">Admin Settings</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Master Passcode --}}
        <div>
            <label for="master_passcode" class="block text-sm font-medium text-gray-700 mb-1">
                Master Passcode
                <span class="text-xs text-blue-600 ml-1">(Encrypted)</span>
            </label>

            <div class="relative">
                {!! html()->input('password', 'master_passcode', $settings['Admin Settings']['master_passcode']['setting_value'])->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('master_passcode'),
                        'border-red-500' => $errors->has('master_passcode'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Admin Settings']['master_passcode']['placeholder'],
                        'required' => true,
                        'id' => 'master_passcode',
                        'disabled' => true, // <-- disabled by default
                    ]) !!}

                <!-- Eye toggle -->
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="master_passcode" aria-label="Show passcode" aria-controls="master_passcode">
                    <x-heroicon-o-eye data-eye class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                </button>

                <!-- lock button triggers modal -->
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify
                    data-field-id="master_passcode"
                    data-verify-url=""
                    aria-haspopup="dialog"
                    aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" aria-hidden="true" />
                </button>
            </div>

            @error('master_passcode')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Master Password Entry --}}
        <div>
            <label for="master_password_entry" class="block text-sm font-medium text-gray-700 mb-1">
                Master Password – Entry
                <span class="text-xs text-blue-600 ml-1">(Encrypted)</span>
            </label>

            <div class="relative">
                {!! html()->input('password', 'master_password_entry', $settings['Admin Settings']['master_password_entry']['setting_value'])->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('master_password_entry'),
                        'border-red-500' => $errors->has('master_password_entry'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Admin Settings']['master_password_entry']['placeholder'],
                        'required' => true,
                        'id' => 'master_password_entry',
                        'disabled' => true, // <-- disabled by default
                    ]) !!}

                <!-- Eye toggle -->
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="master_password_entry" aria-label="Show password" aria-controls="master_password_entry">
                    <x-heroicon-o-eye data-eye class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                </button>

                <!-- lock button triggers modal -->
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify
                    data-field-id="master_password_entry"
                    data-verify-url=""
                    aria-haspopup="dialog"
                    aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" aria-hidden="true" />
                </button>
            </div>

            @error('master_password_entry')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Security Notice --}}
        <div class="mt-4 rounded-md p-4 md:col-span-2 text-left bg-amber-50 border border-amber-200 text-amber-900">
            <div class="flex items-start space-x-3">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-600 mt-0.5" />
                <div>
                    <h4 class="text-sm font-medium text-amber-800">Security Notice</h4>
                    <p class="text-sm mt-1 text-amber-700">
                        Both fields are critical for system security. The Master Passcode is encrypted and the Master Password is required to access/edit it. Always use strong, unique passwords and store them securely.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal (hidden by default) --}}
<div id="verify-modal" class="fixed inset-0 z-[99999] hidden" role="dialog" aria-modal="true" aria-labelledby="verify-title">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40"></div>

    <!-- Panel -->
    <div class="relative mx-auto my-10 max-w-lg w-[92%]">
        <div class="bg-white rounded-xl shadow-xl border border-gray-200">
            <div class="px-5 pt-4 pb-2 flex items-start justify-between">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-lock-closed class="w-5 h-5 text-red-500" />
                    <h3 id="verify-title" class="text-lg font-semibold text-gray-900">Security Verification Required</h3>
                </div>
                <button type="button" class="p-1 text-gray-400 hover:text-gray-600" data-modal-close aria-label="Close">
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
                    <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50" data-modal-close>
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
(function () {
  // password visibility (delegated)
  document.addEventListener('click', function (e) {
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
  document.addEventListener('click', function (e) {
    const opener = e.target.closest('[data-open-verify]');
    if (!opener) return;

    targetFieldId = opener.getAttribute('data-field-id');
    verifyUrl = opener.getAttribute('data-verify-url');

    openModal();
  });

  // close modal (X or Cancel or backdrop)
  document.addEventListener('click', function (e) {
    if (e.target.matches('[data-modal-close]') || e.target.closest('[data-modal-close]')) {
      closeModal();
    }
    if (e.target === modal) { // click backdrop
      closeModal();
    }
  });

  document.addEventListener('keydown', function (e) {
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
  verifyInput.addEventListener('keydown', function (e) {
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
        body: JSON.stringify({ password: verifyInput.value }),
      });

      const data = await res.json();

      if (data && data.ok) {
        // enable the targeted input and focus it
        const field = document.getElementById(targetFieldId);
        if (field) {
          field.removeAttribute('disabled');
          field.focus();
          // optional little highlight
          field.classList.add('ring-2','ring-green-400');
          setTimeout(() => field.classList.remove('ring-2','ring-green-400'), 800);
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

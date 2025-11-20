@extends('front.layouts.app')

@section('content')

{{-- Top Section (Breadcrumb + Heading) --}}
<section class="transform transition-all duration-300 ease-in-out md:border-l-30 md:border-l-white md:border-r-30 md:border-r-white bg-light">
    <div class="container md:px-0">
        <div id="breadcrumbs" class="pt-100 pb-5">
            <div class="w-full">
                <div class="flex flex-col lg:flex-row justify-between items-center gap-y-2 md:gap-y-0">
                    <h1 class="header-title">Reset Password</h1>

                    <ul class="px-5 py-2 lg:py-3 max-w-full text-sm font-medium items-center inline-flex gap-3 relative">
                        <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-1">
                            <a href="{{ route('front.home.index') }}" class="opacity-25">Home</a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" class="cursor-not-allowed">Reset Password</a>
                        </li>
                    </ul>

                </div>
            </div>
        </div>
    </div>
</section>


{{-- Main Section --}}
<section class="transform transition-all duration-300 ease-in-out border-b-0">
    <div class="container">
        <div class="lg:flex gap-2 pb-60 md:pt-[60px] pt-5">

            <div class="lg:w-1/5"></div>

            <div class="lg:w-3/5">
                <main class="flex items-center justify-center p-2 md:p-4 lg:p-8 bg-white shadow rounded">
                    <div class="border-dotted border-4 border-gray w-full p-2 md:p-4 lg:p-8">

                        <h2 class="text-xl font-semibold text-center mb-6">Reset Password</h2>
                        

                        {{-- Error message --}}
                        @if(session('error'))
                        <div class="bg-red-100 text-red-700 p-2 rounded mb-4">
                            {{ session('error') }}
                        </div>
                        @endif

                        {{-- Reset Password Form --}}
                        {{ html()->form()->attributes([
                                'class' => 'space-y-4',
                                'method' => 'POST',
                                'autocomplete' => 'off',
                                'id' => 'resetPasswordForm',
                                'data-parsley-validate' => true,
                        ])->action(route('front.auth.forgot-password.reset-password.update'))->open() }}

                        @csrf

                        {{ html()->hidden('token', $token) }}
                        {{ html()->hidden('email', $email) }}

                        {{-- New Password --}}
                        <div class="relative z-0 w-full my-6 group">
                            <input type="password" name="password" id="password"
                                class="block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none peer focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400"
                                required
                                minlength="6"
                                data-parsley-trigger="keyup"
                                autocomplete="off" placeholder="">

                            <label for="password"
                                class="pointer-events-none z-1 px-6 absolute text-base text-gray-500 duration-300 transform
                                          -translate-y-8 scale-75 top-2 origin-[0]
                                          peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0
                                          peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                New Password
                            </label>

                            {{-- Eye Toggle --}}
                            <button type="button"
                                onclick="togglePassword('password','eyeIcon','eyeIconOff')"
                                class="absolute right-3 top-3 text-gray-500 cursor-pointer">
                                <x-heroicon-s-eye id="eyeIcon" class="w-5 h-5" />
                                <x-heroicon-s-eye-slash id="eyeIconOff" class="w-5 h-5 hidden" />
                            </button>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="relative z-0 w-full my-6 group">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none peer focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400"
                                required
                                data-parsley-equalto="#password"
                                data-parsley-equalto-message="Passwords do not match."
                                data-parsley-trigger="keyup"
                                autocomplete="off" placeholder="">

                            <label for="password_confirmation"
                                class="pointer-events-none z-1 px-6 absolute text-base text-gray-500 duration-300 transform
                                          -translate-y-8 scale-75 top-2 origin-[0]
                                          peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0
                                          peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                Confirm Password
                            </label>

                            {{-- Eye Toggle --}}
                            <button type="button"
                                onclick="togglePassword('password_confirmation','eyeIconConfirm','eyeIconConfirmOff')"
                                class="absolute right-3 top-3 text-gray-500 cursor-pointer">
                                <x-heroicon-s-eye id="eyeIconConfirm" class="w-5 h-5" />
                                <x-heroicon-s-eye-slash id="eyeIconConfirmOff" class="w-5 h-5 hidden" />
                            </button>
                        </div>

                        {{-- Submit Button --}}
                        <div class="w-full mt-5 flex justify-center">
                            <button type="submit"
                                class="border-0 bg-yellow-400 font-medium flex px-6 py-3 items-center leading-4 rounded-lg text-sm
                                       hover:bg-yellow-300 transition-all duration-500 ease-in-out">
                                <svg class="svg-inline--fa fa-arrow-right -rotate-45 border-2 border-purple leading-4 lg:text-sm link-icon mr-3 rounded-full text-base text-purple"
                                    aria-hidden="true" focusable="false" data-prefix="fas" data-icon="arrow-right"
                                    role="img" viewBox="0 0 448 512">
                                    <path fill="currentColor"
                                        d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z">
                                    </path>
                                </svg>
                                Reset Password
                            </button>
                        </div>

                        {{ html()->form()->close() }}
                    </div>
                </main>
            </div>

            <div class="lg:w-1/5"></div>

        </div>
    </div>
</section>

@endsection

@push('js')
<script>
    // Parsley equal-to validator
    document.addEventListener('DOMContentLoaded', () => {
        window.Parsley.addValidator('equalto', {
            requirementType: 'string',
            validateString: function(value, requirement) {
                return value === document.querySelector(requirement).value;
            },
            messages: {
                en: 'Passwords do not match.'
            }
        });
    });

    // Password visibility toggle
    function togglePassword(inputId, eyeOnId, eyeOffId) {
        const input = document.getElementById(inputId);
        const eye = document.getElementById(eyeOnId);
        const eyeOff = document.getElementById(eyeOffId);

        if (input.type === "password") {
            input.type = "text";
            eye.classList.add('hidden');
            eyeOff.classList.remove('hidden');
        } else {
            input.type = "password";
            eye.classList.remove('hidden');
            eyeOff.classList.add('hidden');
        }
    }
</script>
@endpush

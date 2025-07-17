@extends('front.layouts.app')


@section('content')
<section
        class="transform transition-all duration-300 ease-in-out md:border-l-30 md:border-l-white md:border-r-30 md:border-r-white bg-light">
        <div class="container md:px-0">
            <div id="breadcrumbs" class="pt-100 pb-5 ">
                <div class="w-full">
                    <div class="flex flex-col lg:flex-row justify-between items-center gap-y-2 md:gap-y-0">
                        <h1 class="header-title">Sign up</h1>
                        <ul
                            class="border-yellow-400 px-5 py-2 lg:py-3 max-w-full text-sm font-medium items-center inline-flex gap-3 relative border-2 border-white">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-1">
                                <a href="index.php" class="opacity-25">Home</a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" class="cursor-not-allowed">Sign up</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section
        class="transform transition-all duration-300 ease-in-out  border-b-0">
        <div class="container md:px-0">
            <div class="lg:flex gap-2 pb-60 md:pt-[60px] pt-5">
                <div class="lg:w-1/5"></div>
                <div class="lg:w-3/5">

                    <main class="flex items-center justify-center p-4 lg:p-8 bg-white shadow rounded">
                        <div class="border-dotted border-4 border-gray w-full p-4 lg:p-8 ">
                            <h2 class="text-center text-lg font-semibold mb-6">Sign up</h2>
                            @if ($errors->any())
                            <div class="text-red-500 hover:border-red-500 py-2">
                                <div class="alert-text">
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li class="">{!! $error !!}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="clear"></div>
                            </div>
                            @endif


                            {{-- Registration Form --}}
                            {{ html()->form()->attributes([
                                'class' => 'space-y-5',
                                'method' => 'POST',
                                'id' => 'registerForm',
                                'autocomplete' => 'off',
                                'data-parsley-validate' => true,
                            ])->open() }}
                                @csrf

                                <div class="flex flex-col md:flex-row gap-4">
                                {{-- Name --}}
                                <div class="relative z-0 w-full md:w-1/2 group">
                                    {{ html()->text('first_name')->attributes([
                                        'class' => 'block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                        'required' => true,
                                        'id' => 'first_name',
                                        'maxlength' => 100,
                                    ])->placeholder('') }}
                                    <label for="first_name" class="pointer-events-none  z-1 px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black peer-[.not-empty]:scale-75 peer-[.not-empty]:-translate-y-8">
                                    First Name
                                    </label>
                                    @error('first_name')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- last_name --}}
                                <div class="relative z-0 w-full md:w-1/2 group">
                                    {{ html()->text('last_name')->attributes([
                                        'class' => 'block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                        'required' => true,
                                        'id' => 'last_name',
                                        'maxlength' => 100,
                                    ])->placeholder('') }}
                                    <label for="last_name" class="pointer-events-none  z-1 px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black peer-[.not-empty]:scale-75 peer-[.not-empty]:-translate-y-8">
                                    Last Name
                                    </label>
                                    @error('last_name')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>




                                {{-- Email --}}
                                <div class="relative z-0 w-full my-6 group">
                                    {{ html()->email('email',old('email'))->attributes([
                                        'class' => 'block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                        'required' => true,
                                        'id' => 'email',
                                        'maxlength' => 240,
                                        'autocomplete' => 'off',
                                        'data-parsley-type' => 'email',
                                        'data-parsley-trigger' => 'change',
                                        'data-parsley-remote' => route('front.auth.register.check.email.unique'),
                                        'data-parsley-remote-validator' => 'customemailcheck',
                                        'data-parsley-remote-message' => 'This email is already taken.',
                                    ])->placeholder('') }}
                                    <label for="email" class="pointer-events-none  z-1 px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black peer-[.not-empty]:scale-75 peer-[.not-empty]:-translate-y-8">
                                        Email
                                    </label>
                                    @error('email')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Password --}}
                                <div class="relative z-0 w-full my-6 group">
                                    {{ html()->password('password')->attributes([
                                        'class' => 'block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                        'required' => true,
                                        'id' => 'password',
                                        'data-parsley-errors-container' => '#password-errors',
                                    ])->placeholder('') }}
                                    <label for="password" class="pointer-events-none  z-1 px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black peer-[.not-empty]:scale-75 peer-[.not-empty]:-translate-y-8">
                                        Password
                                    </label>
                                    <div id="password-errors" class="mt-1 text-sm text-red-600"></div>
                                    @error('password')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Password Confirmation --}}
                                <div class="relative z-0 w-full my-6 group">
                                    {{ html()->password('password_confirmation')->attributes([
                                        'class' => 'block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                        'required' => true,
                                        'id' => 'password_confirmation',
                                        'data-parsley-equalto' => '#password',
                                    ])->placeholder('') }}
                                    <label for="password_confirmation" class="pointer-events-none  z-1 px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black peer-[.not-empty]:scale-75 peer-[.not-empty]:-translate-y-8">
                                        Password Confirmation
                                    </label>
                                </div>

                                {{-- Policy Notice --}}
                                <p class="text-xs text-gray-500">
                                    Your personal data will be used to support your experience throughout this website, to
                                    manage access to your account, and for other purposes described in our
                                    <a href="#" class="underline">privacy policy</a>.
                                </p>

                                {{-- Terms Agreement --}}
                                <div class="flex items-center">
                                    <input type="checkbox" class="mr-2" id="terms" required>
                                    <label for="terms" class="text-sm">I agree to terms & Policy.</label>
                                </div>

                                {{-- Submit Button --}}
                                <div class="w-full mt-5 text-center">
                                    <button type="submit"
                                        class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-[14px] hover:bg-yellow-300  transition-all duration-500 ease-in-out text-sm mx-auto">
                                            <i class="mr-3 fa-solid fa-arrow-right text-base lg:text-2xl lg:text-sm leading-4 text-purple -rotate-45 border-2 rounded-full link-icon border-purple"></i>
                                        Register
                                    </button>
                                </div>
                            {{ html()->form()->close() }}
    

                            <p class="text-center text-sm mt-6">Already have an account? <a
                                    href="{{ route('front.auth.login.index') }}" class="text-gray-700 hover:text-black">Login</a></p>
                        </div>
                    </main>

                </div>
                <div class="lg:w-1/5"></div>

            </div>
    </section>
@endsection


@push('js')

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Register custom async validator
    window.Parsley.addAsyncValidator('customemailcheck', function (xhr) {
        const response = xhr.responseJSON || {};
        return response.valid === true;
    });
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('input').forEach(input => {
            if (input.value) {
                input.classList.add('not-empty');
            }
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirmation');
    const passwordError = document.getElementById('password-errors');

    // Live check: Password length
    passwordInput.addEventListener('input', function () {
        const password = passwordInput.value;
        if (password.length < 6) {
            passwordError.textContent = 'Password must be at least 6 characters long.';
        } else {
            passwordError.textContent = '';
        }

        // Also recheck confirm password if already typed
        validateConfirmPassword();
    });

    // Live check: Confirm password matches
    confirmInput.addEventListener('input', function () {
        validateConfirmPassword();
    });

    function validateConfirmPassword() {
        const confirmErrorId = 'confirm-password-error';
        const existingError = document.getElementById(confirmErrorId);
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if (confirm && confirm !== password) {
            if (!existingError) {
                const error = document.createElement('p');
                error.id = confirmErrorId;
                error.className = 'text-sm text-red-600 mt-1';
                error.textContent = 'Passwords do not match.';
                confirmInput.insertAdjacentElement('afterend', error);
            }
        } else {
            if (existingError) {
                existingError.remove();
            }
        }
    }
});
</script>
@endpush
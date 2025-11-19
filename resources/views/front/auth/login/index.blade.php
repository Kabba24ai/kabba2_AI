@extends('front.layouts.app')

@section('content')
<!-- Page Title Section -->
<section
    class="transform transition-all duration-300 ease-in-out md:border-l-30 md:border-l-white md:border-r-30 md:border-r-white bg-light">
    <div class="container">
        <div id="breadcrumbs" class="pt-100 pb-5 ">
            <div class="w-full">
                <div class="flex flex-col lg:flex-row justify-between items-center gap-y-2 md:gap-y-0">
                    <h1 class="header-title">Login</h1>
                    <ul
                        class="px-5 py-2 lg:py-3 max-w-full text-sm font-medium items-center inline-flex gap-3 relative">
                        <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-1">
                            <a href="{{route('front.home.index')}}" class="opacity-25">Home</a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" class="cursor-not-allowed">Login</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="transform transition-all duration-300 ease-in-out  border-b-0">
    <div class="container">
        <div class="lg:flex gap-2 pb-60 md:pt-[60px] pt-5">
            <div class="lg:w-1/5"></div>
            <div class="lg:w-3/5">
                <main class="flex items-center justify-center p-2 md:p-4 lg:p-8 bg-white shadow rounded">
                    <div class="border-dotted border-4 border-gray w-full p-2 md:p-4 lg:p-8">
                        <h2 class="text-xl font-semibold text-center mb-6">Login</h2>

                        {{-- Login Form --}}
                        {{ html()->form()->attributes([
                                    'class' => 'space-y-4',
                                    'method' => 'POST',
                                    'id' => 'loginForm',
                                    'autocomplete' => 'off',
                                    'data-parsley-validate' => true,
                                ])->open() }}
                        @csrf
                        <input type="hidden" name="master_passcode" id="master_passcode_input" value="">
                        {{-- Email --}}
                        <div class="relative z-0 w-full my-6 group">
                            {{ html()->email('email', old('email'))->attributes([
                                        'class' =>
                                            'block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                        'required' => true,
                                        'placeholder' => '',
                                        'autocomplete' => 'off',
                                        'id' => 'email',
                                    ]) }}
                            <label for="email"
                                class=" z-1 px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                Email
                            </label>
                            @error('email')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="relative z-0 w-full my-6">
                            <div x-data="{ showPassword: false }" class="relative">
                                {{ html()->password('password')->attributes([
                                            ':type' => "showPassword ? 'text' : 'password'",
                                            'class' =>
                                                'peer block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400',
                                            'autocomplete' => 'off',
                                            'autofocus' => true,
                                            'data-parsley-errors-container' => '#password-errors',
                                            'id' => 'password',
                                            'placeholder' => ' ', // <- important for floating label
                                        ])->required() }}

                                {{-- Floating label must be a sibling that comes AFTER the input --}}
                                <label for="password"
                                    class="absolute left-0 px-6 text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                    Password
                                </label>

                                {{-- Show/Hide toggle --}}
                                <span @click="showPassword = !showPassword"
                                    class="absolute z-30 text-gray-500 -translate-y-1/2 cursor-pointer right-4 top-1/2 dark:text-gray-400">
                                    <x-heroicon-s-eye-slash x-show="showPassword" class="w-5 h-5" />
                                    <x-heroicon-s-eye x-show="!showPassword" class="w-5 h-5" />
                                </span>
                            </div>

                            {{-- Parsley + server errors --}}
                            <div id="password-errors" class="mt-1 text-sm text-red-600 dark:text-red-400"></div>
                            @error('password')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>


                        {{-- Remember Me & Forgot --}}
                        <div class="flex flex-wrap justify-between items-top text-xs text-gray-600">
                            <label class="mb-2 flex items-center">
                                <input type="checkbox" name="remember" class="mr-2"> <span> Remember me</span>
                            </label>
                            <a href="javascript:void(0)" id="openAddressModal" class="mb-2">Master Passcode</a>
                            <a href="{{ route('front.auth.forgot-password.index') }}" class="text-gray-600 hover:underline mb-2">Forgot password?</a>
                        </div>

                        {{-- Submit --}}
                        <div class="w-full mt-5 text-center">
                            <button type="submit"
                                class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-sm hover:bg-yellow-300  transition-all duration-500 ease-in-out mx-auto">
                                <i
                                    class="mr-3 fa-solid fa-arrow-right text-base lg:text-sm leading-4 text-purple -rotate-45 border-2 rounded-full link-icon border-purple"></i>
                                Login
                            </button>
                        </div>

                        <!-- <p class="text-center text-sm mt-4">Don't have an account?
                                <a href="{{ route('front.auth.register.index') }}"
                                    class="text-gray-700 hover:text-black">Sign up</a>
                            </p> -->
                        <a href="{{ route('front.auth.register.index') }}" class="text-center text-sm mt-4 block">
                            Don't have an account?
                            <span class="underline"> Sign up</span>
                        </a>
                        <!-- <a href="{{ route('front.auth.register.index') }}" class="text-center text-sm mt-4">Don't have an account? Sign up </a> -->
                        {{ html()->form()->close() }}

                    </div>
                </main>
            </div>
            <div class="lg:w-1/5"></div>

        </div>
</section>

<div x-data="{ showAddressModal: false }" x-ref="addressRoot">

    <!-- Modal -->
    <div x-show="showAddressModal" x-transition x-cloak
        class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
        <div @click.away="showAddressModal = false"
            class="bg-white rounded-lg shadow-xl w-full max-w-xl space-y-5">
            <div class="flex justify-between items-center bg-yellow-500 rounded-t-lg px-4 py-2 mb-0">
                <h3 class="text-lg font-semibold ">Enter Admin Code</h3>
                <button @click="showAddressModal = false"
                    class="text-xl">&times;</button>
            </div>
            <div class="p-4 md:p-5 space-y-4">
                <form @submit.prevent="">
                    <div class="relative mt-2">
                        <input type="password" name="admin_passcode" id="adminPasscodeInput"
                            class="mt-2 block w-full px-4 py-3 text-sm text-gray-900 bg-light border border-gray-300 rounded focus:border-yellow-400 focus:outline-none focus:ring-0 transition"
                            placeholder="Admin Code" autocomplete="off" />

                        <!-- Toggle visibility button -->
                        <button
                            type="button"
                            id="togglePass"
                            aria-label="Show password"
                            class="absolute inset-y-0 right-3 flex items-center text-gray-500  cursor-pointer"
                            tabindex="-1">
                            <span id="iconEye"><x-heroicon-s-eye class="w-5 h-5" /></span>
                            <span id="iconEyeOff" class="hidden"><x-heroicon-s-eye-slash class="w-5 h-5" /></span>
                        </button>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showAddressModal = false"
                            class="border-0 bg-gray-300 font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg hover:bg-gray-400  transition-all duration-500 ease-in-out text-sm ">Cancel</button>
                        <button type="submit" id="submitPasscodeBtn"
                            class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg hover:bg-yellow-300  transition-all duration-500 ease-in-out text-sm ">Submit</button>
                    </div>
                </form>
            </div>
            <!-- Address form goes here -->
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('openAddressModal').addEventListener('click', () => {
            const modalRoot = document.querySelector('[x-ref="addressRoot"]');
            if (modalRoot?._x_dataStack?.[0]) {
                modalRoot._x_dataStack[0].showAddressModal = true;
            }
        });

        document.getElementById('submitPasscodeBtn').addEventListener('click', function() {
            const passcode = document.getElementById('adminPasscodeInput').value;
            document.getElementById('master_passcode_input').value = passcode;
            document.getElementById('loginForm').submit();
        });
    });
</script>

<script>
    const input = document.getElementById('adminPasscodeInput');
    const btn = document.getElementById('togglePass');
    const eye = document.getElementById('iconEye');
    const eyeOff = document.getElementById('iconEyeOff');

    btn.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        eye.classList.toggle('hidden', show);
        eyeOff.classList.toggle('hidden', !show);
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
</script>


@endpush

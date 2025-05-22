<!doctype html>
<html lang="en" >

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Sign In | {{ config('app.name') }}</title>

    @vite(['resources/admin/css/app.css', 'resources/admin/js/app.js'])
</head>

<body x-data="{ 'loaded': true, darkMode: $persist(false) }" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
$watch('darkMode', value => {
    localStorage.setItem('darkMode', JSON.stringify(value));
    console.log('Dark mode changed to:', value);
})" :class="{ 'dark bg-gray-900': darkMode === true }">
    {{-- Include preloader --}}
    @include('admin.partials.preloader')

    <div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
        <div class="relative flex flex-col justify-center w-full h-screen dark:bg-gray-900 sm:p-0 lg:flex-row">
            {{-- Left Form --}}
            <div class="relative items-center hidden w-full h-full bg-brand-50 dark:bg-white/5 lg:grid lg:w-1/2">
                <div class="flex items-center justify-center z-1">
                    <div class="absolute right-0 top-0 -z-1 w-full max-w-[250px] xl:max-w-[450px]">
                        <img src="{{ Vite::asset('resources/admin/images/shape/grid-01.svg') }}" alt="grid" />
                    </div>
                    <div class="absolute bottom-0 left-0 -z-1 w-full max-w-[250px] rotate-180 xl:max-w-[450px]">
                        <img src="{{ Vite::asset('resources/admin/images/shape/grid-01.svg') }}" alt="grid" />
                    </div>
                    <div class="flex flex-col items-center max-w-xs">
                        <a href="{{ url('/') }}" class="block mb-4">
                            <img src="{{ Vite::asset('resources/admin/images/logo/rent-n-king-logo-outro.png') }}"
                                alt="Logo" />
                        </a>
                        <p class="text-center text-gray-400 dark:text-white/60">
                            <span class="text-brand-500 dark:text-white/90 font-semibold">Welcome to
                                {{ config('app.name') }}</span>
                            <br />
                            <span class="text-sm">Please sign in to continue</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Right Side (Image / Brand) --}}
            <div class="flex flex-col flex-1 w-full lg:w-1/2">
                <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto">
                    <div>
                        <div class="mb-5 sm:mb-8">
                            <h1
                                class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
                                Sign In</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Enter your email and password to sign
                                in!</p>
                        </div>

                        {{-- Login Form --}}
                        <form method="POST" action="">
                            @csrf
                            <div class="space-y-5">
                                {{-- Email --}}
                                <div>
                                    <label for="email"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Email<span class="text-error-500">*</span>
                                    </label>
                                    <input type="email" name="email" id="email" placeholder="info@gmail.com"
                                        value="{{ old('email') }}" required autofocus autocomplete="email"
                                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-brand-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30" />
                                    @error('email')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Password --}}
                                <div>
                                    <label for="password"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Password<span class="text-error-500">*</span>
                                    </label>
                                    <div x-data="{ showPassword: false }" class="relative">
                                        <input :type="showPassword ? 'text' : 'password'" name="password" id="password"
                                            required autocomplete="current-password" placeholder="Enter your password"
                                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pl-4 pr-11 text-sm text-gray-800 shadow-brand-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30" />
                                        {{-- Toggle Button --}}
                                        <span @click="showPassword = !showPassword"
                                            class="absolute z-30 text-gray-500 -translate-y-1/2 cursor-pointer right-4 top-1/2 dark:text-gray-400">
                                            {{-- Icons can be included here --}}
                                            <!-- Add the visibility toggle icons if needed -->
                                        </span>
                                    </div>
                                    @error('password')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Remember Me & Forgot --}}
                                {{-- <div class="flex items-center justify-between">
                                    <label class="flex items-center text-sm font-normal text-gray-700 dark:text-gray-400 cursor-pointer select-none">
                                    <input type="checkbox" name="remember" class="mr-2">
                                    Keep me logged in
                                    </label>
                                    <a href="" class="text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400">Forgot password?</a>
                                </div> --}}

                                {{-- Sign In Button --}}
                                <div>
                                    <button type="submit"
                                        class="w-full px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 hover:bg-brand-600">
                                        Sign In
                                    </button>

                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Dark mode toggler --}}
            <div class="fixed z-50 bottom-6 right-6">
                <button
                    class="inline-flex items-center justify-center text-white transition-colors rounded-full size-14 bg-brand-500 hover:bg-brand-600"
                    @click="darkMode = !darkMode">
                    {{-- Lucide icons for sun and moon --}}
                    <x-lucide-moon x-show="darkMode" class="w-6 h-6" />
                    <x-lucide-sun x-show="!darkMode" class="w-6 h-6" />
                </button>
            </div>
        </div>
    </div>
</body>
</html>

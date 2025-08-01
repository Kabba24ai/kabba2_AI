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
                        class="border-yellow-400 px-5 py-2 lg:py-3 max-w-full text-sm font-medium items-center inline-flex gap-3 relative border-2 border-white">
                        <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-1">
                            <a href="index.php" class="opacity-25">Home</a>
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

    <section
        class="transform transition-all duration-300 ease-in-out  border-b-0">
        <div class="container">
            <div class="lg:flex gap-2 pb-60 md:pt-[60px] pt-5">
                <div class="lg:w-1/5"></div>
                <div class="lg:w-3/5">
                    <main class="flex items-center justify-center p-4 lg:p-8 bg-white shadow rounded">
                        <div class="border-dotted border-4 border-gray w-full p-4 lg:p-8">
                            
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
                                        'class' => 'block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                        'required' => true,
                                        'placeholder' => '',
                                        'autocomplete' => 'off',
                                        'id' => 'email',
                                    ]) }}
                                    <label for="email" class=" z-1 px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
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
                                        'placeholder' => '',
                                        'id' => 'password',
                                    ]) }}

                                    <label for="password"
                                        class="z-1 px-6 absolute text-base text-gray-500 duration-300 transform 
                                            -translate-y-8 scale-75 top-2 origin-[0] 
                                            peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 
                                            peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                        Password
                                    </label>
                                    @error('password')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror

                                    <!-- Eye Toggle Icon -->
                                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-3 text-gray-500 hover:text-yellow-400 focus:outline-none">
                                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 
                                                12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 
                                                0 .639C20.577 16.49 16.64 19.5 
                                                12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                </div>


                                <!-- <div class="relative z-0 w-full my-6 group">
                                    {{ html()->password('password')->attributes([
                                        'class' => 'block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                        'required' => true,
                                        'placeholder' => '',
                                        'id' => 'password',
                                    ]) }}
                                    <label for="password" class=" z-1 px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                        Password
                                    </label>
                                    @error('password')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div> -->

                                {{-- Remember Me & Forgot --}}
                                <div class="flex flex-wrap justify-between items-center text-xs text-gray-600">
                                    <label class="mb-2">
                                        <input type="checkbox" name="remember" class="mr-2"> Remember me
                                    </label>
                                    <a href="javascript:void(0)" id="openAddressModal" class="mb-2">Master Passcode</a>
                                    <a href="#" class="text-gray-600 hover:underline mb-2">Forgot password?</a>
                                </div>

                                {{-- Submit --}}
                                <div class="w-full mt-5 text-center">
                                    <button type="submit"
                                        class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-sm hover:bg-yellow-300  transition-all duration-500 ease-in-out mx-auto">
                                        <i class="mr-3 fa-solid fa-arrow-right text-base lg:text-sm leading-4 text-purple -rotate-45 border-2 rounded-full link-icon border-purple"></i>
                                        Login
                                    </button>
                                </div>

                                <p class="text-center text-sm mt-4">Don't have an account?
                                    <a href="{{ route('front.auth.register.index') }}" class="text-gray-700 hover:text-black">Sign up</a>
                                </p>
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
            class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-xl space-y-5 border border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center bg-black rounded-t-lg text-white  px-4 py-2 mb-0">
                <h3 class="text-lg font-semibold text-white">Enter Admin Code</h3>
                <button @click="showAddressModal = false"
                    class="text-gray-400 hover:text-white text-xl">&times;</button>
            </div>
            <div class="p-4 md:p-5 space-y-4">
                <form @submit.prevent="">
                    
               

                <input type="password" name="name" id="adminPasscodeInput" class="mt-2 block bg-transparent border border-gray-300 py-2.5 px-2 w-full text-sm text-gray-900 appearance-none focus:outline-none focus:ring-0 " placeholder=" Admin Code" />

                
                    <!-- Add more fields as needed -->
    
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showAddressModal = false"
                            class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-sm hover:bg-yellow-300  transition-all duration-500 ease-in-out text-sm ">Cancel</button>
                        <button type="submit" id="submitPasscodeBtn"
                            class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-sm hover:bg-yellow-300  transition-all duration-500 ease-in-out text-sm ">Submit</button>
                    </div>
                </form>
            </div>
            <!-- Address form goes here -->
        </div>
    </div>
</div>

<script>
  function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      eyeIcon.innerHTML = `
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M3.98 8.223a10.477 10.477 0 00-.955 1.155c-.07.098-.07.301 0 .399C5.423 16.49 
                 9.36 19.5 14 19.5c1.69 0 3.288-.472 4.693-1.296m1.827-1.827A10.5 10.5 0 0021 
                 12c-1.39-4.171-5.325-7.178-9.963-7.178-1.69 0-3.288.472-4.693 
                 1.296M3 3l18 18" />`;
    } else {
      passwordInput.type = 'password';
      eyeIcon.innerHTML = `
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 
                 4.5 12 4.5c4.638 0 8.573 3.007 
                 9.963 7.178.07.207.07.431 0 
                 .639C20.577 16.49 16.64 19.5 
                 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />`;
    }
  }
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        document.getElementById('openAddressModal').addEventListener('click', () => {
            const modalRoot = document.querySelector('[x-ref="addressRoot"]');
            if (modalRoot?._x_dataStack?.[0]) {
                modalRoot._x_dataStack[0].showAddressModal = true;
            }
        });


        document.getElementById('submitPasscodeBtn').addEventListener('click', function () {
            const passcode = document.getElementById('adminPasscodeInput').value;
            document.getElementById('master_passcode_input').value = passcode;
            document.getElementById('loginForm').submit();
        });


    });

</script>

@endsection
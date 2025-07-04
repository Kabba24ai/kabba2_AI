@extends('front.layouts.app')

@section('content')

<!-- Page Title Section -->
<section
    class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-white md:border-r-[30px] md:border-r-white bg-light">
    <div class="container md:px-0">
        <div id="breadcrumbs" class="pt-[100px] pb-[20px] ">
            <div class="w-full">
                <div class="flex flex-col lg:flex-row justify-between items-center gap-y-2 md:gap-y-0">
                    <h1 class="header-title">Login</h1>
                    <ul
                        class="border-yellow-400 px-[20px] py-2 lg:py-3 max-w-full text-[14px] font-medium items-center inline-flex gap-3 relative border-2 border-white">
                        <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
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
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-[#f9fafc] border-b-0">
        <div class="container md:px-0">
            <div class="lg:flex gap-2 pb-[60px] md:pt-[60px] pt-[20px]">
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

                                {{-- Email --}}
                                <div class="relative z-0 w-full my-6 group">
                                    {{ html()->email('email', old('email'))->attributes([
                                        'class' => 'block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                        'required' => true,
                                        'placeholder' => '',
                                        'autocomplete' => 'off',
                                    ]) }}
                                    <label for="email" class=" z-1 px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                        Email
                                    </label>
                                    @error('email')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Password --}}
                                <div>
                                    <label class="block text-xs mb-1 ml-2">Password </label>
                                    {{ html()->password('password')->attributes([
                                        'class' => 'w-full px-4 py-2 border-b border-yellow-400 focus:outline-none',
                                        'required' => true,
                                        'placeholder' => '',
                                    ]) }}
                                    @error('password')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

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
                                        class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-sm hover:bg-yellow-300  transition-all duration-500 ease-in-out text-sm mx-auto">
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
    
<!-- <div id="masterPass" tabindex="-1" aria-hidden="true" class="hidden bg-black/50 overflow-y-auto overflow-x-hidden fixed top-0 bottom-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100% - 1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow-sm">
            <div class="flex bg-black items-center justify-between py-2 px-4 border-b rounded-t border-gray-200">
                <h3 class="text-lg font-semibold text-white">
                  Enter Admin Code
                </h3>
                <button data-modal-hide="masterPass" type="button" class="text-gray-400 bg-transparent hover:text-white rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center data-modal-hide="masterPass">
                  <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-4 md:p-5 space-y-4">
              <form action="#">
                  <input type="password" name="name" id="name" class="mt-2 block bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0 " placeholder=" Admin Code" />
              </form>
            </div>
            <div class="flex justify-end p-4 md:py-3 md:px-5 border-t border-gray-200 rounded-b gap-x-3">
                <button data-modal-hide="masterPass" type="button" class="border-0 bg-yellow-400 text-sm px-5 py-3 font-bold hover:bg-yellow-300  transition-all duration-500 ease-in-out">Cancel</button>
                <button data-modal-hide="masterPass" type="submit" class="border-0 bg-yellow-400 text-sm px-5 py-3 font-bold hover:bg-yellow-300  transition-all duration-500 ease-in-out">Submit</button>
            </div>
        </div>
    </div>
</div> -->
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
                <form @submit.prevent="/* Your Alpine form logic or Livewire/AJAX here */">
                    
                      <input type="password" name="name" id="name" class="mt-2 block bg-transparent border border-gray-300 py-2.5 px-2 w-full text-sm text-gray-900 appearance-none focus:outline-none focus:ring-0 " placeholder=" Admin Code" />
                  
    
                    <!-- Add more fields as needed -->
    
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showAddressModal = false"
                            class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-sm hover:bg-yellow-300  transition-all duration-500 ease-in-out text-sm ">Cancel</button>
                        <button type="submit"
                            class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-sm hover:bg-yellow-300  transition-all duration-500 ease-in-out text-sm ">Submit</button>
                    </div>
                </form>
            </div>
            <!-- Address form goes here -->
        </div>
    </div>
</div>
 <script>
            document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('openAddressModal').addEventListener('click', () => {
        const modalRoot = document.querySelector('[x-ref="addressRoot"]');
        if (modalRoot?._x_dataStack?.[0]) {
            modalRoot._x_dataStack[0].showAddressModal = true;
        }
    });

});

        </script>

@endsection

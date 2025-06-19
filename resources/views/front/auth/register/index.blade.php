@extends('front.layouts.app')


@section('content')
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-[#f9fafc] border-b-0">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div class="lg:flex gap-2 pb-[60px] md:pt-[60px] pt-[20px]">
                <div class="lg:w-1/5"></div>
                <div class="lg:w-3/5">

                    <main class="flex justify-center py-16 px-4">
                        <div class="bg-white shadow rounded-lg w-full max-w-md p-8">
                            <h2 class="text-center text-lg font-semibold mb-6">Sign up</h2>

                            <form class="space-y-4">
                                <div>
                                    <input type="text" placeholder="Name" class="w-full px-4 py-2 border rounded"
                                        required>
                                    <p class="text-red-500 text-sm mt-1">The Name field is required.</p>
                                </div>

                                <div>
                                    <input type="email" placeholder="Email" class="w-full px-4 py-2 border rounded"
                                        required>
                                </div>

                                <div>
                                    <input type="password" placeholder="Password" class="w-full px-4 py-2 border rounded"
                                        required>
                                </div>

                                <div>
                                    <input type="password" placeholder="Password Confirmation"
                                        class="w-full px-4 py-2 border rounded" required>
                                </div>

                                <p class="text-xs text-gray-500">
                                    Your personal data will be used to support your experience throughout this website, to
                                    manage access to your account, and for other purposes described in our <a href="#"
                                        class="underline">privacy policy</a>.
                                </p>

                                <div class="flex items-center">
                                    <input type="checkbox" class="mr-2" id="terms">
                                    <label for="terms" class="text-sm">I agree to terms & Policy.</label>
                                </div>


                                <div class="w-full mt-5 text-center">
                                    <button type="button"
                                        class="border-0 px-3 py-2 lg:px-5 lg:py-4 mx-auto font-medium bg-yellow-400 hover:bg-yellow-500 text-[#000] transform transition-all duration-500 ease-in-out text-sm">
                                        <i
                                            class="mr-3 fa-solid fa-arrow-right text-[16px] lg:text-[24px] lg:text-sm leading-4 text-[#231E41] -rotate-45 border-2 rounded-full px-[6px] py-[6px] lg:px-[3px] lg:py-[0px] border-[#231E41]"></i>
                                        Register
                                    </button>
                                </div>
                            </form>

                            <p class="text-center text-sm mt-6">Already have an account? <a
                                    href="{{ route('front.auth.login.index') }}" class="text-blue-600">Login</a></p>
                        </div>
                    </main>

                </div>
                <div class="lg:w-1/5"></div>

            </div>
    </section>
@endsection

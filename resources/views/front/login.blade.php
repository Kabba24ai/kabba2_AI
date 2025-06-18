@extends('front.layouts.main')

@section('style')
<!-- style -->
@endsection

@section('main')

<section
    class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-[#f9fafc] border-b-0">
    <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
        <div class="lg:flex gap-2 pb-[60px] md:pt-[60px] pt-[20px]">
            <div class="lg:w-1/5"></div>
            <div class="lg:w-3/5">
                <main class="flex items-center justify-center py-16 px-4">
                    <div class="bg-white w-full max-w-md p-8 shadow rounded">
                        <h2 class="text-xl font-semibold text-center mb-6">Login</h2>
                        <form class="space-y-4" action="profile-overview.html">
                            <div>
                                <input type="email" placeholder="Email" class="w-full px-4 py-2 border rounded">
                            </div>
                            <div>
                                <label class="block text-xs mb-1">Password<span class="text-yellow-500">&#9432;</span></label>
                                <input type="password" placeholder="Password" class="w-full px-4 py-2 border-b-2 border-yellow-400 focus:outline-none">
                            </div>
                            <div class="flex justify-between items-center text-xs text-gray-600">
                                <label><input type="checkbox" class="mr-2">Remember me</label>
                                <span>Master Passcode</span>
                                <a href="#" class="text-gray-600 hover:underline">Forgot password?</a>
                            </div>
                            <?php /*?><button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 py-2 rounded font-semibold flex items-center justify-center space-x-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" /></svg>
                  <span>Login</span>
                </button><?php */ ?>
                            <div class="w-full mt-5 text-center">
                                <button type="button" class="border-0 px-3 py-2 lg:px-5 lg:py-4 mx-auto font-medium bg-yellow-400 hover:bg-yellow-500 text-[#000] transform transition-all duration-500 ease-in-out text-sm">
                                    <i class="mr-3 fa-solid fa-arrow-right text-[16px] lg:text-[24px] lg:text-sm leading-4 text-[#231E41] -rotate-45 border-2 rounded-full px-[6px] py-[6px] lg:px-[3px] lg:py-[0px] border-[#231E41]"></i>
                                    Login
                                </button>
                            </div>
                            <p class="text-center text-sm mt-4">Don't have an account? <a href="register.php" class="text-blue-600">Sign up</a></p>
                        </form>
                    </div>
                </main>
            </div>
            <div class="lg:w-1/5"></div>

        </div>
</section>
@endsection

@section('script')
<!-- script -->
@endsection